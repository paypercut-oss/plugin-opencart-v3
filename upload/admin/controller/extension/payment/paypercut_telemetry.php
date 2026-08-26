<?php

use Paypercut\Support\Environment;
use Paypercut\Telemetry\ActiveExtensions;
use Paypercut\Telemetry\Bootstrap;
use Paypercut\Telemetry\Context;
use Paypercut\Telemetry\EnvironmentSnapshot;
use Paypercut\Telemetry\Event;
use Paypercut\Telemetry\EventQueue;
use Paypercut\Telemetry\Flusher;
use Paypercut\Telemetry\MintErrorMapper;
use Paypercut\Telemetry\SentLog;
use Paypercut\Telemetry\Store;
use Paypercut\Telemetry\TelemetrySession;
use Paypercut\Telemetry\TokenMinter;

/**
 * Paypercut Debug Session
 * Merchant-facing controls for the time-boxed diagnostic feed, plus the three
 * authenticated endpoints the panel talks to. Delivery happens here and only
 * here: an authenticated admin request is the only place events are sent from.
 */
class ControllerExtensionPaymentPaypercutTelemetry extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);

        require_once DIR_SYSTEM . 'library/paypercut/bootstrap.php';

        Bootstrap::boot($registry);
    }

    /**
     * Render the panel in all four of its states.
     *
     * The server paints the current state so the panel is correct with no round
     * trip; the script then keeps the countdown and counters live. Reaping and
     * one delivery attempt happen here too — this is the backstop for a
     * merchant who started a session and navigated away from the poll.
     */
    public function panel()
    {
        $this->load->language('extension/payment/paypercut_telemetry');

        Bootstrap::loadAdmin();

        Store::ensureSchema();
        TelemetrySession::reap();

        $flusher = new Flusher();
        $flusher->flushOnce();

        $state = TelemetrySession::describe();

        $data = $state;
        $data['started_by_name'] = strip_tags($state['started_by_name']);
        $data['now'] = time();
        $data['ends_at'] = $state['expires_at'] > 0 ? date('H:i', $state['expires_at']) : '';
        $data['poll_seconds'] = TelemetrySession::POLL_INTERVAL_SECONDS;
        $data['log_max_entries'] = SentLog::MAX_ENTRIES;
        $data['user_token'] = $this->session->data['user_token'];
        $data['start_url'] = $this->url->link('extension/payment/paypercut_telemetry/start', 'user_token=' . $this->session->data['user_token'], true);
        $data['stop_url'] = $this->url->link('extension/payment/paypercut_telemetry/stop', 'user_token=' . $this->session->data['user_token'], true);
        $data['status_url'] = $this->url->link('extension/payment/paypercut_telemetry/status', 'user_token=' . $this->session->data['user_token'], true);

        $entries = SentLog::all();
        $data['log_entries'] = array();

        foreach ($entries as $entry) {
            $data['log_entries'][] = array(
                'occurred_at' => isset($entry['occurred_at']) ? (string)$entry['occurred_at'] : '—',
                'event' => isset($entry['event']) ? (string)$entry['event'] : '—',
                'detail' => $this->eventDetail($entry)
            );
        }

        $data['log_count'] = count($entries);
        $data['log_raw'] = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Language strings the template reads directly.
        $data = array_merge($this->language->all(), $data);

        return $this->load->view('extension/payment/paypercut_telemetry', $data);
    }

    /**
     * Mint a telemetry token and publish a session.
     */
    public function start()
    {
        $this->load->language('extension/payment/paypercut_telemetry');

        Bootstrap::loadAdmin();

        if (!$this->authorised()) {
            $this->respond(false, array('message' => $this->language->get('error_telemetry_permission')), 403);
            return;
        }

        Store::ensureSchema();
        TelemetrySession::reap();

        $state = TelemetrySession::describe();

        if ($state['state'] === 'running') {
            $state['already_running'] = true;
            $state['now'] = time();

            $this->respond(true, $state);
            return;
        }

        if (!TelemetrySession::claimStartLock()) {
            // Without a real mutex two clicks in two tabs both mint, and the
            // loser's token is referenced by nothing that can revoke it.
            $this->respond(false, array('message' => $this->language->get('error_telemetry_starting')), 409);
            return;
        }

        $result = $this->beginSession();

        // The lock is released before the response is written, so a failure to
        // render can never strand it for its full TTL.
        TelemetrySession::releaseStartLock();

        $this->respond($result['ok'], $result['data'], $result['status']);
    }

    /**
     * End the session early at the merchant's request.
     */
    public function stop()
    {
        $this->load->language('extension/payment/paypercut_telemetry');

        Bootstrap::loadAdmin();

        if (!$this->authorised()) {
            $this->respond(false, array('message' => $this->language->get('error_telemetry_permission')), 403);
            return;
        }

        $record = TelemetrySession::record();
        $runtime = TelemetrySession::runtime();

        if (isset($record['status']) && $record['status'] === 'active') {
            EventQueue::append(
                array(
                    Event::sessionStopped(
                        (string)(isset($record['session_id']) ? $record['session_id'] : ''),
                        'merchant_stopped',
                        (int)(isset($runtime['events_sent']) ? $runtime['events_sent'] : 0),
                        (int)(isset($runtime['events_dropped']) ? $runtime['events_dropped'] : 0)
                    )->envelope()
                )
            );

            // Twice: the first pass clears anything already parked in flight,
            // the second carries the stop event itself. Bounded on purpose —
            // each pass can block for up to the edge timeout, and this is a
            // button click.
            $flusher = new Flusher();

            for ($attempt = 0; $attempt < 2; $attempt++) {
                if (!$flusher->flushOnce()) {
                    break;
                }
            }
        }

        TelemetrySession::end('merchant_stopped');

        $state = TelemetrySession::describe();
        $state['now'] = time();

        $this->respond(true, $state);
    }

    /**
     * The panel's poll, which doubles as the delivery trigger while the
     * merchant has the screen open.
     */
    public function status()
    {
        $this->load->language('extension/payment/paypercut_telemetry');

        Bootstrap::loadAdmin();

        if (!$this->authorised()) {
            $this->respond(false, array('message' => $this->language->get('error_telemetry_permission')), 403);
            return;
        }

        TelemetrySession::reap();

        $flusher = new Flusher();
        $flusher->flushOnce();

        $state = TelemetrySession::describe();
        // `now` travels with `expires_at` so the countdown is driven by the
        // server's clock, not the browser's.
        $state['now'] = time();

        $this->respond(true, $state);
    }

    /**
     * Tell every admin user that this store is currently sending diagnostics.
     *
     * Event handler for admin/view/common/header/after. The module's own logger
     * is gated on a merchant preference, so without this a session could run
     * with no visible trace for anyone but the person who started it.
     */
    public function notice(&$route, &$data, &$output)
    {
        if (!$this->user->hasPermission('access', 'extension/payment/paypercut')) {
            return;
        }

        $record = TelemetrySession::record();

        if (!isset($record['status']) || $record['status'] !== 'active') {
            return;
        }

        if ((int)(isset($record['expires_at']) ? $record['expires_at'] : 0) <= time()) {
            return;
        }

        $this->load->language('extension/payment/paypercut_telemetry');

        $message = sprintf(
            $this->language->get('text_telemetry_notice'),
            htmlspecialchars((string)(isset($record['started_by_name']) ? $record['started_by_name'] : ''), ENT_QUOTES, 'UTF-8'),
            date('H:i', (int)$record['expires_at'])
        );

        $link = $this->url->link('extension/payment/paypercut', 'user_token=' . $this->session->data['user_token'], true);

        $output .= '<div class="container-fluid"><div class="alert alert-info" style="margin-top: 10px;">'
            . '<i class="fa fa-bug"></i> ' . $message
            . ' <a href="' . $link . '">' . $this->language->get('text_telemetry_manage') . '</a>'
            . '</div></div>';
    }

    /**
     * The start sequence, from credential check to the opening events.
     */
    private function beginSession()
    {
        $connection = TelemetrySession::connection();

        if ($connection['secret'] === '') {
            return $this->failure($this->language->get('error_telemetry_no_api_key'), 400);
        }

        // Both hosts come from this one environment value. A token minted for
        // one environment is rejected by every other environment's edge, so
        // they must never be resolved independently.
        $mint_base = Environment::apiBaseUri($connection['environment']);
        $edge_base = Environment::telemetryBaseUri($connection['environment']);

        if ($edge_base === '') {
            $message = $connection['environment'] === ''
                ? $this->language->get('error_telemetry_no_environment')
                : $this->language->get('error_telemetry_unsupported_environment');

            return $this->failure($message, 400);
        }

        $minter = new TokenMinter();
        $response = $minter->mint($connection['secret'], $mint_base);
        $status = (int)$response['status'];

        if ($status !== 200) {
            return $this->reject(MintErrorMapper::map($status, $response['body']), $response, $status);
        }

        if ($response['token'] === '' || $response['expires_at'] === '') {
            return $this->reject(MintErrorMapper::badResponse(), $response, 502);
        }

        $now = time();
        $lifetime = TokenMinter::deriveLifetime($response['expires_at'], $response['date'], $now);
        $skew = TokenMinter::skew($response['date'], $now);

        if ($lifetime < TelemetrySession::MIN_LIFETIME_SECONDS) {
            return $this->reject(MintErrorMapper::clockSkew($skew), $response, 400);
        }

        $expires_at = $now + min($lifetime, TelemetrySession::SESSION_MAX_SECONDS) - TelemetrySession::SKEW_SECONDS;

        // Re-check under the lock: if anything published a session while the
        // mint was in flight, discard this token rather than store a second
        // one. An unreferenced token cannot be deleted by any teardown path.
        $existing = TelemetrySession::describe();

        if ($existing['state'] === 'running') {
            $existing['already_running'] = true;
            $existing['now'] = time();

            return array('ok' => true, 'data' => $existing, 'status' => 200);
        }

        $session_id = TelemetrySession::newSessionId();

        TelemetrySession::begin(
            array(
                'status' => 'active',
                'session_id' => $session_id,
                'environment' => $connection['environment'],
                'edge_base' => $edge_base,
                'started_at' => $now,
                'expires_at' => $expires_at,
                'started_by' => (int)$this->user->getId(),
                'started_by_name' => (string)$this->user->getUserName(),
                'key_fingerprint' => TelemetrySession::fingerprint($connection['secret']),
                'ended_at' => 0,
                'reason_code' => '',
                'trace_id' => Event::identifier($response['trace_id']),
                'request_id' => Event::identifier($response['request_id'])
            ),
            $response['token']
        );

        $snapshot = EnvironmentSnapshot::values();

        $envelopes = array(
            Event::sessionStarted($session_id, $connection['environment'], $expires_at)->envelope(),
            Event::environmentSnapshot($snapshot)->envelope(),
            Event::environmentConfiguration($snapshot)->envelope()
        );

        foreach (Event::environmentPlugins(ActiveExtensions::values()) as $event) {
            $envelopes[] = $event->envelope();
        }

        EventQueue::append($envelopes);

        Context::audit(
            'Telemetry: debug session started',
            array(
                'session_id' => $session_id,
                'environment' => $connection['environment'],
                'expires_at' => $expires_at,
                'clock_skew_s' => $skew
            )
        );

        $state = TelemetrySession::describe();
        $state['now'] = time();

        return array('ok' => true, 'data' => $state, 'status' => 200);
    }

    /**
     * Record a start that did not happen, so the merchant can see why.
     */
    private function reject(array $mapped, array $response, $status)
    {
        $trace_id = Event::identifier($response['trace_id']);
        $request_id = Event::identifier($response['request_id']);

        TelemetrySession::fail($mapped, $trace_id, $request_id);

        // No session exists yet, so this cannot be reported as an event.
        Context::audit(
            'Telemetry: mint rejected',
            array(
                'status' => (int)$response['status'],
                'reason_code' => $mapped['reason_code'],
                'trace_id' => $trace_id,
                'request_id' => $request_id
            )
        );

        return array(
            'ok' => false,
            'data' => array(
                'message' => $mapped['message'],
                'reason_code' => $mapped['reason_code'],
                'retryable' => $mapped['retryable'],
                'trace_id' => $trace_id,
                'request_id' => $request_id
            ),
            'status' => $status >= 400 && $status < 600 ? $status : 502
        );
    }

    private function failure($message, $status)
    {
        return array(
            'ok' => false,
            'data' => array('message' => $message),
            'status' => $status
        );
    }

    /**
     * One line summarising an event, so the table is scannable without the JSON.
     */
    private function eventDetail(array $entry)
    {
        $parts = array();
        $error = isset($entry['error']) && is_array($entry['error']) ? $entry['error'] : array();

        if (isset($error['code'])) {
            $parts[] = (string)$error['code'];
        }

        foreach (array('order_ref', 'payment_id', 'payment_intent_id') as $key) {
            if (!empty($entry[$key])) {
                $parts[] = $key . '=' . (string)$entry[$key];
            }
        }

        $attrs = isset($entry['attrs']) && is_array($entry['attrs']) ? $entry['attrs'] : array();

        foreach (array('origin_plugin', 'http_status', 'reason', 'webhook') as $key) {
            if (isset($attrs[$key]) && is_scalar($attrs[$key])) {
                $parts[] = $key . '=' . (string)$attrs[$key];
            }
        }

        // Lifecycle events carry none of the keys above, and a row of dashes
        // tells the merchant nothing.
        if (empty($parts)) {
            foreach ($attrs as $key => $value) {
                if (count($parts) >= 3) {
                    break;
                }

                if (is_scalar($value)) {
                    $parts[] = $key . '=' . (is_bool($value) ? ($value ? 'true' : 'false') : (string)$value);
                }
            }
        }

        return empty($parts) ? '—' : implode(' · ', $parts);
    }

    private function authorised()
    {
        if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token'])) {
            return false;
        }

        if ($this->request->get['user_token'] !== $this->session->data['user_token']) {
            return false;
        }

        return $this->user->hasPermission('modify', 'extension/payment/paypercut');
    }

    private function respond($ok, array $data, $status = 200)
    {
        if ($status !== 200) {
            $this->response->addHeader('HTTP/1.1 ' . (int)$status);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(
            json_encode(
                array(
                    'success' => (bool)$ok,
                    'data' => $data
                )
            )
        );
    }
}
