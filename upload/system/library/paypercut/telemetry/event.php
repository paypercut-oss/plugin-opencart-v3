<?php

namespace Paypercut\Telemetry;

/**
 * A single diagnostic event, and the allow-list that defines what may leave the store.
 *
 * There is deliberately no generic "record these fields" constructor. Every
 * event is built by a named constructor with declared parameters, so the set of
 * things that can ever be transmitted is fixed in this file rather than at each
 * call site. is_scalar() is explicitly NOT the boundary — every secret this
 * module holds (API key, webhook secret) is a scalar string living in the same
 * settings group as the values we do report.
 */
class Event
{
    /**
     * Longest string any single field may carry, in BYTES.
     *
     * Bytes rather than codepoints because the edge bounds the raw Go string: a
     * 128-codepoint CJK theme name is 384 bytes and would be dropped whole.
     */
    const MAX_TEXT_BYTES = 256;

    /**
     * The edge keeps the first attributes in sorted key order and drops the
     * rest, so a single over-wide event would silently lose its version fields.
     */
    const MAX_ATTRS = 16;

    const MAX_STACK_FRAMES = 8;

    /**
     * Field names that must never appear in an event, whatever their value.
     */
    const DENIED_KEY_PATTERN = '/secret|token|password|credential|nonce|auth|_key$/i';

    /**
     * Value shapes that must never appear, whatever their field name.
     *
     * Not anchored to the start of the string, because a stack frame or an HTTP
     * error carries the credential mid-string every time; not left unanchored
     * either, because bare sk_/pk_ also matches `disk_usage` and `risk_free`
     * and a tripped assertion bins the whole event.
     */
    const DENIED_VALUE_PATTERN = '/(?:^|[^A-Za-z0-9_])(ppc_|sk_|pk_|whsec_|eyJ[A-Za-z0-9_-]+\.)/i';

    /**
     * Host and platform versions. Read by environmentSnapshot().
     *
     * Both snapshot lists are iterated INSTEAD of the caller's array: pulling
     * keys out of a settings array is how a credential ends up on the wire.
     */
    private static $snapshot_fields = array(
        'plugin_version' => 'text',
        'oc_version' => 'text',
        'php_version' => 'text',
        'theme_name' => 'text',
        'theme_version' => 'text',
        'is_multistore' => 'bool',
        'is_ssl' => 'bool'
    );

    /**
     * Module settings. Read by environmentConfiguration().
     */
    private static $configuration_fields = array(
        'checkout_mode' => 'identifier',
        'order_status' => 'identifier',
        'google_pay' => 'bool',
        'apple_pay' => 'bool',
        'logging_enabled' => 'bool',
        'statement_descriptor_set' => 'bool',
        'payment_method_config_set' => 'bool',
        'connection_environment' => 'identifier',
        'api_key_mode' => 'identifier',
        'webhook_configured' => 'bool',
        'payment_domain_registered' => 'bool',
        'card_enabled' => 'bool',
        'store_currency' => 'identifier'
    );

    private $name;

    private $fields;

    /**
     * Contract-level correlation fields, sent outside `attrs`.
     */
    private $correlation = array();

    private $error = array();

    private function __construct($name, array $fields)
    {
        $this->name = $name;
        $this->fields = $fields;
    }

    /**
     * Report something that happened and did not fail.
     *
     * Failures alone cannot answer the commonest support question, which is
     * whether the shopper ever reached us.
     */
    public static function of($name, array $attrs = array())
    {
        return new self($name, self::cleanAttrs($attrs));
    }

    /**
     * Report a failure, under whichever event name describes where it happened.
     */
    public static function failure($name, $code, array $attrs = array(), $exception = null)
    {
        $event = new self($name, self::cleanAttrs($attrs));

        $event->error = array('code' => self::text($code) ? self::text($code) : 'unknown');

        if ($exception !== null) {
            $event->error['type'] = self::shortClassName($exception);
            $event->error['message'] = self::text($exception->getMessage());
            $event->error['stack'] = self::stack($exception);

            $event->fields = array_merge(self::origin(self::frameFiles($exception)), $event->fields);
        }

        return $event;
    }

    /**
     * Report a Paypercut API rejection with the fields the platform returned.
     *
     * The upstream message is never copied: the platform quotes submitted input
     * back, so a rejected API key arrives inside it. `api_code` and `trace_id`
     * carry the diagnosis instead.
     *
     * @param int   $status HTTP status of the rejection.
     * @param array $body   Decoded response body, empty when unparsable.
     */
    public static function apiFailure($name, $status, array $body = array(), array $attrs = array())
    {
        $event = self::failure($name, 'http_' . (int)$status, $attrs);

        $error = isset($body['error']) && is_array($body['error']) ? $body['error'] : array();

        $type = self::text((string)(isset($error['type']) ? $error['type'] : ''));

        if ($type !== '') {
            $event->error['type'] = $type;
        }

        $candidates = array(
            'api_code' => isset($error['code']) ? $error['code'] : (isset($body['code']) ? $body['code'] : ''),
            'api_param' => isset($error['param']) ? $error['param'] : '',
            'trace_id' => isset($body['trace_id']) ? $body['trace_id'] : (isset($error['trace_id']) ? $error['trace_id'] : '')
        );

        foreach ($candidates as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $clean = self::text((string)$value);

            if ($clean !== '') {
                $event->fields[$key] = $clean;
            }
        }

        $event->fields['http_status'] = (int)$status;

        return $event;
    }

    /**
     * Report the fatal that ended a request.
     *
     * Built from error_get_last(), which carries no exception and no trace —
     * the file that died is the only attribution available.
     */
    public static function fatal($message, $file, $line, $level)
    {
        $event = new self('php.fatal', array('level' => (int)$level));

        $event->fields = array_merge(self::origin(array($file)), $event->fields);

        $event->error = array(
            'code' => 'php_fatal',
            'type' => 'FatalError',
            'message' => self::text(self::fatalMessage($message)),
            'stack' => array(self::relativePath($file) . ':' . (int)$line)
        );

        return $event;
    }

    public static function sessionStarted($session_id, $environment, $expires_at)
    {
        // Note what is absent: the admin user who started the session. The
        // durable record keeps it for the notice, but a store-user identifier
        // is not covered by the merchant-facing disclosure.
        return new self(
            'session.started',
            array(
                'session_id' => self::identifier($session_id),
                'environment' => self::identifier($environment),
                'expires_at' => (int)$expires_at
            )
        );
    }

    public static function sessionStopped($session_id, $reason, $events_sent, $events_dropped)
    {
        return new self(
            'session.stopped',
            array(
                'session_id' => self::identifier($session_id),
                'reason' => self::identifier($reason),
                'events_sent' => (int)$events_sent,
                'events_dropped' => (int)$events_dropped
            )
        );
    }

    /**
     * @param array $values Candidate values; only the declared schema keys are read.
     */
    public static function environmentSnapshot(array $values)
    {
        return new self('environment.snapshot', self::castFields(self::$snapshot_fields, $values));
    }

    /**
     * Separate from the environment snapshot only because the two together
     * exceed MAX_ATTRS; nothing else distinguishes them.
     *
     * @param array $values Candidate values; only the declared schema keys are read.
     */
    public static function environmentConfiguration(array $values)
    {
        return new self('environment.configuration', self::castFields(self::$configuration_fields, $values));
    }

    /**
     * The installed-extension inventory, chunked to fit the attribute cap.
     *
     * This is the list support compares against a working store when a conflict
     * is suspected. Codes and versions only — no author, no path.
     *
     * @param array $extensions code => version, sorted by the caller.
     *
     * @return Event[]
     */
    public static function environmentPlugins(array $extensions)
    {
        $total = count($extensions);
        $chunks = array_chunk($extensions, self::MAX_ATTRS - 2, true);
        $events = array();

        foreach ($chunks as $index => $chunk) {
            $fields = array(
                'plugin_count' => $total,
                'chunk' => $index + 1
            );

            foreach ($chunk as $code => $version) {
                $key = self::text((string)$code);

                if ($key !== '') {
                    $fields[$key] = self::text((string)$version);
                }
            }

            $events[] = new self('environment.plugins', $fields);
        }

        return $events;
    }

    /**
     * Attach the ids that join this event to a payment.
     */
    public function about(array $correlation)
    {
        foreach (array('payment_intent_id', 'payment_id', 'order_ref') as $field) {
            $value = trim((string)(isset($correlation[$field]) ? $correlation[$field] : ''));

            if ($value !== '') {
                $this->correlation[$field] = self::text($value);
            }
        }

        return $this;
    }

    /**
     * A message the module authored itself, for a failure with no exception
     * worth quoting.
     */
    public function because($message)
    {
        $clean = self::text($message);

        if ($clean !== '') {
            $this->error['message'] = $clean;
        }

        return $this;
    }

    public function name()
    {
        return $this->name;
    }

    public function fields()
    {
        return $this->fields;
    }

    /**
     * The wire shape of a single event inside a batch.
     *
     * The contract's field is `occurred_at`, an RFC3339 STRING; sending a unix
     * int under that name fails the whole event, so name and type move together.
     *
     * @param int|null $now Injected clock, so the suite can pin timestamps.
     */
    public function envelope($now = null)
    {
        $envelope = array(
            'event' => $this->name,
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z', $now === null ? time() : $now)
        );

        foreach ($this->correlation as $field => $value) {
            $envelope[$field] = $value;
        }

        if (!empty($this->error)) {
            $envelope['error'] = $this->error;
        }

        // PHP renders an empty array as [], which the edge reads as "not an
        // object" and records as a drop against an otherwise clean event.
        if (!empty($this->fields)) {
            $envelope['attrs'] = $this->fields;
        }

        return $envelope;
    }

    /**
     * Hard deny assertion: true when this event must be dropped entirely.
     *
     * A safety net behind the named constructors, not the primary control. It
     * drops the whole event rather than the offending field, because a field
     * that trips it means the event was assembled wrongly and the rest of it
     * cannot be trusted either.
     */
    public static function isDenied(array $fields, array $secrets = array(), $depth = 0)
    {
        foreach ($fields as $key => $value) {
            if (preg_match(self::DENIED_KEY_PATTERN, (string)$key)) {
                return true;
            }

            // The contract nests one level — `error`, and `error.stack` inside
            // it. Without recursion the assertion sees a non-string and gives
            // up, which is exactly where free text now lives.
            if (is_array($value)) {
                if ($depth < 2 && self::isDenied($value, $secrets, $depth + 1)) {
                    return true;
                }

                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            if (preg_match(self::DENIED_VALUE_PATTERN, $value)) {
                return true;
            }

            if (self::containsCardNumber($value)) {
                return true;
            }

            // Shape matching is a guess; comparing against the store's actual
            // credentials is not. This catches a secret in a format nobody
            // anticipated, including one a future Paypercut release introduces.
            foreach ($secrets as $secret) {
                if (is_string($secret) && $secret !== '' && strpos($value, $secret) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A Luhn-valid 13-19 digit run anywhere in the value.
     *
     * The edge screens for a PAN too, but only when the whole value is one:
     * "Card 4111111111111111 was declined" passes it. Card data must never
     * leave a merchant estate, so the client is the right place to enforce it.
     */
    public static function containsCardNumber($value)
    {
        if (!preg_match_all('/\d(?:[ -]?\d){12,18}/', $value, $matches)) {
            return false;
        }

        foreach ($matches[0] as $candidate) {
            if (self::luhnValid(preg_replace('/\D/', '', $candidate))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Free-ish text: printable characters only, hard byte cap.
     *
     * UTF-8 is preserved rather than stripped — a Greek or Japanese theme name
     * is one of the more useful diagnostics there is. Only control characters go.
     */
    public static function text($value)
    {
        $value = (string)$value;
        $clean = (string)preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        if ($clean === '' && $value !== '') {
            // Invalid UTF-8 made the unicode-mode replace fail; fall back to ASCII.
            $clean = (string)preg_replace('/[^\x20-\x7E]/', '', $value);
        }

        // mb_strcut cuts on a byte budget while respecting codepoint
        // boundaries; mb_substr counts codepoints and would overshoot.
        return function_exists('mb_strcut')
            ? mb_strcut($clean, 0, self::MAX_TEXT_BYTES)
            : substr($clean, 0, self::MAX_TEXT_BYTES);
    }

    /**
     * Identifier-shaped values only; anything else is dropped, never mangled.
     */
    public static function identifier($value)
    {
        return preg_match('/^[A-Za-z0-9_.:-]{1,64}$/', (string)$value) ? (string)$value : '';
    }

    /**
     * The class name without its namespace.
     *
     * Public because a call site that must not send an exception's message
     * still wants to name its type — a rejected credential is quoted back in
     * the message but never in the class.
     */
    public static function shortClassName($exception)
    {
        $parts = explode('\\', get_class($exception));

        $name = self::text((string)end($parts));

        return $name === '' ? 'Throwable' : $name;
    }

    /**
     * Attribute a failure to the code that raised it.
     *
     * The commonest support case is another extension breaking ours, and the
     * answer is in the stack: the first frame outside our own files names it.
     *
     * @param array $files Absolute paths, innermost first.
     */
    public static function origin(array $files)
    {
        foreach ($files as $file) {
            $file = (string)$file;

            if (self::isOurs($file)) {
                continue;
            }

            $relative = self::relativePath($file);

            if (preg_match('#(^|/)view/theme/([^/]+)/#', $relative)) {
                return array('origin' => 'theme');
            }

            if (preg_match('#(^|/)extension/[^/]+/([^/]+)\.php$#', $relative, $matches)) {
                return array(
                    'origin' => 'plugin',
                    'origin_plugin' => self::text($matches[2])
                );
            }

            return array('origin' => 'core');
        }

        return array('origin' => 'paypercut');
    }

    /**
     * Paths relative to the OpenCart install: an absolute path on shared
     * hosting names the merchant's account or domain.
     */
    public static function relativePath($file)
    {
        foreach (self::roots() as $root) {
            if ($root !== '' && strpos($file, $root) === 0) {
                return ltrim(substr($file, strlen($root)), '/');
            }
        }

        return '[external]';
    }

    /**
     * Known filesystem roots, longest first so the most specific one wins.
     */
    private static function roots()
    {
        $roots = array();

        foreach (array('DIR_CATALOG', 'DIR_APPLICATION', 'DIR_SYSTEM') as $constant) {
            if (defined($constant)) {
                $roots[] = (string)constant($constant);
            }
        }

        if (defined('DIR_APPLICATION')) {
            $roots[] = rtrim(dirname(rtrim((string)constant('DIR_APPLICATION'), '/')), '/') . '/';
        }

        usort(
            $roots,
            function ($left, $right) {
                return strlen($right) - strlen($left);
            }
        );

        return $roots;
    }

    /**
     * Files this module owns, so origin attribution skips past them.
     */
    private static function isOurs($file)
    {
        return strpos($file, '/paypercut/') !== false || strpos(basename($file), 'paypercut') === 0;
    }

    /**
     * Absolute file paths from a throwable, its own location first.
     */
    private static function frameFiles($exception)
    {
        $files = array($exception->getFile());

        foreach ($exception->getTrace() as $frame) {
            if (isset($frame['file'])) {
                $files[] = (string)$frame['file'];
            }
        }

        return $files;
    }

    /**
     * File and line only, at most MAX_STACK_FRAMES of them.
     *
     * Never getTraceAsString(): that renders call arguments, which here are
     * checkout payloads and credentials.
     */
    private static function stack($exception)
    {
        $frames = array();

        foreach ($exception->getTrace() as $frame) {
            if (count($frames) >= self::MAX_STACK_FRAMES) {
                break;
            }

            if (!isset($frame['file']) || !isset($frame['line'])) {
                continue;
            }

            $frames[] = self::relativePath((string)$frame['file']) . ':' . (int)$frame['line'];
        }

        return $frames;
    }

    /**
     * Reduce PHP's fatal message to the part that is not already reported.
     *
     * An uncaught Error arrives with its whole trace inlined and every path
     * absolute; left alone it spends the clamp on frames the `stack` field
     * already carries, and puts the server's filesystem layout on the wire.
     */
    private static function fatalMessage($message)
    {
        $message = (string)$message;
        $trace = strpos($message, 'Stack trace:');

        if ($trace !== false) {
            $message = rtrim(substr($message, 0, $trace));
        }

        foreach (self::roots() as $root) {
            if ($root !== '') {
                $message = str_replace($root, '', $message);
            }
        }

        return $message;
    }

    private static function castFields(array $schema, array $values)
    {
        $fields = array();

        foreach ($schema as $key => $cast) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];

            if ($cast === 'bool') {
                $fields[$key] = (bool)$value;
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $clean = $cast === 'identifier' ? self::identifier((string)$value) : self::text((string)$value);

            if ($clean !== '') {
                $fields[$key] = $clean;
            }
        }

        return $fields;
    }

    /**
     * Bound attributes a call site passed in, rather than trusting them.
     *
     * Booleans and ints are already bounded and pass through intact; strings
     * are clamped and control-stripped; a container is not a scalar diagnostic
     * and is dropped.
     */
    private static function cleanAttrs(array $attrs)
    {
        $fields = array();

        foreach ($attrs as $key => $value) {
            if (count($fields) >= self::MAX_ATTRS) {
                break;
            }

            $name = self::text((string)$key);

            if ($name === '' || !is_scalar($value)) {
                continue;
            }

            $fields[$name] = is_string($value) ? self::text($value) : $value;
        }

        return $fields;
    }

    private static function luhnValid($digits)
    {
        $length = strlen($digits);

        if ($length < 13 || $length > 19) {
            return false;
        }

        $sum = 0;
        $double = false;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$digits[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }
}
