<?php

namespace Paypercut\Telemetry;

use Paypercut\Support\Environment;

/**
 * Owns the debug (telemetry) session: its state, its storage, and its teardown.
 *
 * A debug session is a merchant-granted, self-expiring window during which the
 * store may send diagnostic events to Paypercut. The deadline is an absolute
 * unix timestamp in the durable record, and every read recomputes liveness
 * against it — that is what makes the session end on time with no scheduled
 * job. The token's own `exp` is the matching bound on the server side.
 */
class TelemetrySession
{
    /**
     * Hard ceiling on a session, independent of what the mint hands back.
     *
     * With no revocation anywhere, this ceiling IS the consent: the merchant is
     * told "about 60 minutes", so the module must not run for longer even if a
     * future deployment issues longer-lived tokens.
     */
    const SESSION_MAX_SECONDS = 3600;

    /**
     * Give up this many seconds before the token actually expires, so the
     * module always stops before the edge would start rejecting it.
     */
    const SKEW_SECONDS = 30;

    const MIN_LIFETIME_SECONDS = 60;

    const MAX_QUEUE_EVENTS = 200;

    const MAX_QUEUE_BYTES = 65536;

    /**
     * Kept well under the edge's 64 KiB body cap: the edge does not deduplicate,
     * so a bigger batch only means losing more events per failed POST.
     */
    const MAX_BATCH_BYTES = 16384;

    /**
     * The edge's own MaxEventsPerBatch. A batch over it is refused with 413.
     */
    const MAX_BATCH_EVENTS = 50;

    const MAX_CONSECUTIVE_SEND_FAILURES = 4;

    const MINT_TIMEOUT_SECONDS = 10;

    const MINT_CONNECT_TIMEOUT_SECONDS = 5;

    const EDGE_TIMEOUT_SECONDS = 5;

    const EDGE_CONNECT_TIMEOUT_SECONDS = 3;

    const START_LOCK_TTL = 60;

    const FLUSH_LOCK_TTL = 60;

    const FAILED_NOTICE_TTL = 600;

    const ENDED_NOTICE_TTL = 86400;

    const POLL_INTERVAL_SECONDS = 60;

    const SLOW_REQUEST_MS = 3000;

    const TOKEN_KEY = 'paypercut_telemetry_token';

    const QUEUE_KEY = 'paypercut_telemetry_queue';

    const INFLIGHT_KEY = 'paypercut_telemetry_inflight';

    const RUNTIME_KEY = 'paypercut_telemetry_runtime';

    const START_LOCK_KEY = 'paypercut_telemetry_start_lock';

    const FLUSH_LOCK_KEY = 'paypercut_telemetry_flush_lock';

    const API_KEY_SETTING = 'payment_paypercut_api_key';

    const WEBHOOK_SECRET_SETTING = 'payment_paypercut_webhook_secret';

    const ENVIRONMENT_SETTING = 'payment_paypercut_environment';

    /**
     * Per-request memo for the storefront gate.
     */
    private static $active_memo = null;

    /**
     * The storefront gate: is a session live right now?
     *
     * Reads one setting OpenCart has already loaded for this request and
     * nothing else — no extra query, no write, no HTTP.
     */
    public static function isActiveFast()
    {
        if (self::$active_memo !== null) {
            return self::$active_memo;
        }

        $record = self::record();

        self::$active_memo = (isset($record['status']) && $record['status'] === 'active')
            && (int)(isset($record['expires_at']) ? $record['expires_at'] : 0) > time();

        return self::$active_memo;
    }

    /**
     * Forget the per-request memo. Only state transitions need this.
     */
    public static function flushMemo()
    {
        self::$active_memo = null;
    }

    public static function record()
    {
        return Store::record();
    }

    /**
     * The session state as the admin UI should present it.
     */
    public static function describe()
    {
        $record = self::record();
        $runtime = self::runtime();
        $now = time();

        $status = isset($record['status']) ? (string)$record['status'] : '';
        $state = 'idle';

        if ($status === 'active') {
            $state = (int)(isset($record['expires_at']) ? $record['expires_at'] : 0) > $now ? 'running' : 'ended';
        } elseif ($status === 'failed') {
            $state = ($now - (int)(isset($record['ended_at']) ? $record['ended_at'] : 0)) < self::FAILED_NOTICE_TTL ? 'failed' : 'idle';
        } elseif ($status === 'stopped' || $status === 'expired') {
            $state = ($now - (int)(isset($record['ended_at']) ? $record['ended_at'] : 0)) < self::ENDED_NOTICE_TTL ? 'ended' : 'idle';
        }

        return array(
            'state' => $state,
            'session_id' => isset($record['session_id']) ? (string)$record['session_id'] : '',
            'expires_at' => (int)(isset($record['expires_at']) ? $record['expires_at'] : 0),
            'started_at' => (int)(isset($record['started_at']) ? $record['started_at'] : 0),
            'ended_at' => (int)(isset($record['ended_at']) ? $record['ended_at'] : 0),
            'started_by_name' => isset($record['started_by_name']) ? (string)$record['started_by_name'] : '',
            'reason_code' => isset($record['reason_code']) ? (string)$record['reason_code'] : '',
            'trace_id' => isset($record['trace_id']) ? (string)$record['trace_id'] : '',
            'request_id' => isset($record['request_id']) ? (string)$record['request_id'] : '',
            'retryable' => (bool)(isset($record['retryable']) ? $record['retryable'] : false),
            'message' => isset($record['message']) ? (string)$record['message'] : '',
            'events_sent' => (int)(isset($runtime['events_sent']) ? $runtime['events_sent'] : 0),
            'events_dropped' => (int)(isset($runtime['events_dropped']) ? $runtime['events_dropped'] : 0),
            'queued' => EventQueue::size()
        );
    }

    /**
     * The telemetry token, or '' when there is not a usable one.
     *
     * Every condition here is a reason the token must not be used, and each is
     * checked rather than assumed: the stored expiry is a backstop, never the
     * authority.
     */
    public static function token()
    {
        $record = self::record();

        if (!isset($record['status']) || $record['status'] !== 'active') {
            return '';
        }

        $expires_at = (int)(isset($record['expires_at']) ? $record['expires_at'] : 0);

        if ($expires_at <= time()) {
            return '';
        }

        $stored = Store::get(self::TOKEN_KEY);

        if (!is_array($stored) || !isset($stored['token']) || !is_string($stored['token'])) {
            return '';
        }

        if ((int)(isset($stored['expires_at']) ? $stored['expires_at'] : 0) !== $expires_at) {
            return '';
        }

        if (!self::credentialMatches($record)) {
            return '';
        }

        $decoded = base64_decode($stored['token'], true);

        return is_string($decoded) ? $decoded : '';
    }

    /**
     * Does the stored record still describe the connection the store has today?
     */
    public static function credentialMatches(array $record)
    {
        $connection = self::connection();
        $fingerprint = self::fingerprint($connection['secret']);

        if ($fingerprint === '' || $fingerprint !== (string)(isset($record['key_fingerprint']) ? $record['key_fingerprint'] : '')) {
            return false;
        }

        return $connection['environment'] === (string)(isset($record['environment']) ? $record['environment'] : '');
    }

    /**
     * The module's stored credential and environment.
     *
     * The environment is returned raw rather than defaulted: a store that has
     * never chosen one must get no session rather than a session pointed at an
     * environment nobody selected.
     */
    public static function connection()
    {
        $environment = trim((string)Context::setting(self::ENVIRONMENT_SETTING, ''));

        return array(
            'secret' => (string)Context::setting(self::API_KEY_SETTING, ''),
            'environment' => in_array($environment, Environment::choices(), true) ? $environment : ''
        );
    }

    /**
     * Every credential the store holds, for the deny assertion to compare against.
     *
     * Comparing a value against the actual secret is the only screen that
     * catches a format nobody anticipated, and it is silently useless for a
     * setting not named here — a future gateway adding its own credential
     * setting breaks it.
     */
    public static function credentials()
    {
        $secrets = array(self::token());

        foreach (array(self::API_KEY_SETTING, self::WEBHOOK_SECRET_SETTING) as $key) {
            $value = Context::setting($key, '');

            if (is_string($value) && $value !== '') {
                $secrets[] = $value;
            }
        }

        // An empty secret would match every string.
        return array_values(array_filter($secrets));
    }

    /**
     * A short, non-reversing marker for "the same API key as before".
     */
    public static function fingerprint($secret)
    {
        return $secret === '' ? '' : substr(hash('sha256', $secret), 0, 12);
    }

    /**
     * Publish a new session and store its token.
     */
    public static function begin(array $record, $jwt)
    {
        $expires_at = (int)$record['expires_at'];

        Store::put(
            self::TOKEN_KEY,
            array(
                'token' => base64_encode($jwt),
                'expires_at' => $expires_at
            ),
            max(60, $expires_at - time())
        );

        // Never inherit a previous session's buffer: those events were gathered
        // under a different consent and would ship under this session's id.
        Store::delete(self::QUEUE_KEY);
        Store::delete(self::INFLIGHT_KEY);

        // A previous session's tail would misattribute events the merchant is
        // reading to decide what happened.
        SentLog::clear();

        Store::putRecord($record);

        Store::put(
            self::RUNTIME_KEY,
            array(
                'events_sent' => 0,
                'events_dropped' => 0,
                'consecutive_edge_failures' => 0,
                'next_attempt_at' => 0,
                'last_error' => ''
            )
        );

        self::flushMemo();
    }

    /**
     * Record a start that never happened, so the merchant sees why.
     */
    public static function fail(array $mapped, $trace_id = '', $request_id = '')
    {
        $record = self::record();

        // Never overwrite a live session with a failure notice: a concurrent
        // start that loses a race would strand the winner's token.
        if (isset($record['status']) && $record['status'] === 'active') {
            return;
        }

        Store::putRecord(
            array(
                'status' => 'failed',
                'ended_at' => time(),
                'reason_code' => $mapped['reason_code'],
                'message' => $mapped['message'],
                'retryable' => $mapped['retryable'],
                'trace_id' => $trace_id,
                'request_id' => $request_id
            )
        );

        self::flushMemo();
    }

    /**
     * End the session and destroy every trace of its credential.
     *
     * Idempotent, and the single teardown path: expiry, the Stop button, a
     * re-key, an environment change and uninstall all arrive here, so there is
     * exactly one place that can forget something.
     */
    public static function end($reason)
    {
        $record = self::record();

        Store::delete(self::TOKEN_KEY);
        Store::delete(self::QUEUE_KEY);
        Store::delete(self::INFLIGHT_KEY);

        if (empty($record) || !isset($record['status']) || $record['status'] !== 'active') {
            Store::delete(self::RUNTIME_KEY);
            self::flushMemo();
            return;
        }

        $runtime = self::runtime();

        Store::putRecord(
            array(
                'status' => $reason === 'expired' ? 'expired' : 'stopped',
                'session_id' => (string)(isset($record['session_id']) ? $record['session_id'] : ''),
                'environment' => (string)(isset($record['environment']) ? $record['environment'] : ''),
                'started_at' => (int)(isset($record['started_at']) ? $record['started_at'] : 0),
                'expires_at' => (int)(isset($record['expires_at']) ? $record['expires_at'] : 0),
                'started_by' => (int)(isset($record['started_by']) ? $record['started_by'] : 0),
                'started_by_name' => (string)(isset($record['started_by_name']) ? $record['started_by_name'] : ''),
                'ended_at' => time(),
                'reason_code' => $reason,
                'events_sent' => (int)(isset($runtime['events_sent']) ? $runtime['events_sent'] : 0),
                'events_dropped' => (int)(isset($runtime['events_dropped']) ? $runtime['events_dropped'] : 0)
            )
        );

        Store::delete(self::RUNTIME_KEY);

        self::flushMemo();

        Context::audit(
            'Telemetry: debug session ended',
            array(
                'session_id' => (string)(isset($record['session_id']) ? $record['session_id'] : ''),
                'reason' => $reason
            )
        );
    }

    /**
     * Tear down a session whose deadline has passed, or whose connection changed.
     *
     * Admin context only. This is what turns "the gate is closed" into "the
     * token is gone": the gate flips the instant the deadline passes, but the
     * stored copy is removed by the next admin request that runs this.
     */
    public static function reap()
    {
        $record = self::record();

        if (!isset($record['status']) || $record['status'] !== 'active') {
            // No live session, but a stored token means the record was lost
            // without one. The credential is now referenced by nothing, so
            // destroy it here rather than leave it to expire.
            if (Store::get(self::TOKEN_KEY) !== null) {
                self::end('token_orphaned');
            }

            return;
        }

        if ((int)(isset($record['expires_at']) ? $record['expires_at'] : 0) <= time()) {
            self::end('expired');
            return;
        }

        if (!self::credentialMatches($record)) {
            self::end('connection_changed');
            return;
        }

        if (self::token() === '') {
            self::end('token_lost');
        }
    }

    public static function runtime()
    {
        $runtime = Store::get(self::RUNTIME_KEY);

        return is_array($runtime) ? $runtime : array();
    }

    public static function updateRuntime(array $values)
    {
        Store::put(self::RUNTIME_KEY, array_merge(self::runtime(), $values));
    }

    /**
     * Claim an exclusive right to mint.
     *
     * Without a real mutex, two clicks in two tabs both mint. The loser's token
     * is then either overwritten or discarded — and either way one fully valid
     * credential exists that no teardown path knows about.
     */
    public static function claimStartLock()
    {
        return Store::claimLock(self::START_LOCK_KEY, self::START_LOCK_TTL);
    }

    public static function releaseStartLock()
    {
        Store::releaseLock(self::START_LOCK_KEY);
    }

    public static function claimFlushLock()
    {
        return Store::claimLock(self::FLUSH_LOCK_KEY, self::FLUSH_LOCK_TTL);
    }

    public static function releaseFlushLock()
    {
        Store::releaseLock(self::FLUSH_LOCK_KEY);
    }

    /**
     * A session identifier: "dbg_" plus 16 random alphanumerics.
     */
    public static function newSessionId()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $id = '';

        for ($i = 0; $i < 16; $i++) {
            $id .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];
        }

        return 'dbg_' . $id;
    }
}
