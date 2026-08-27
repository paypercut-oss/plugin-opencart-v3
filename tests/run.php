<?php

/**
 * Dependency-free test runner for the telemetry units.
 *
 * The units under test are the ones that must not drift: the privacy contract,
 * the environment pairing, the flusher's decision table and the queue bounds.
 * Run with `php tests/run.php` — nothing here needs OpenCart or a database.
 */

$root = dirname(__DIR__);

require_once $root . '/upload/system/library/paypercut/version.php';
require_once $root . '/upload/system/library/paypercut/support/environment.php';
require_once $root . '/upload/system/library/paypercut/telemetry/context.php';
require_once $root . '/upload/system/library/paypercut/telemetry/store.php';
require_once $root . '/upload/system/library/paypercut/telemetry/telemetrysession.php';
require_once $root . '/upload/system/library/paypercut/telemetry/event.php';
require_once $root . '/upload/system/library/paypercut/telemetry/eventqueue.php';
require_once $root . '/upload/system/library/paypercut/telemetry/sentlog.php';
require_once $root . '/upload/system/library/paypercut/telemetry/edgeclient.php';
require_once $root . '/upload/system/library/paypercut/telemetry/flusher.php';

use Paypercut\Support\Environment;
use Paypercut\Telemetry\Event;
use Paypercut\Telemetry\EventQueue;
use Paypercut\Telemetry\Flusher;
use Paypercut\Telemetry\TelemetrySession;

$failures = array();
$assertions = 0;

function check($name, $condition)
{
    global $failures, $assertions;

    $assertions++;

    if (!$condition) {
        $failures[] = $name;
        echo "FAIL  " . $name . PHP_EOL;
    }
}

function same($name, $expected, $actual)
{
    check($name . ' (expected ' . json_encode($expected) . ', got ' . json_encode($actual) . ')', $expected === $actual);
}

// --- The environment pairing ------------------------------------------------

same('api base: dev', 'https://api.dev.paypercut.net/', Environment::apiBaseUri('dev'));
same('api base: stage', 'https://api.stage.paypercut.net/', Environment::apiBaseUri('stage'));
same('api base: production', 'https://api.paypercut.io/', Environment::apiBaseUri('production'));
same('api base: unknown falls back to production', 'https://api.paypercut.io/', Environment::apiBaseUri('nonsense'));
same('api base: unset falls back to production', 'https://api.paypercut.io/', Environment::apiBaseUri(''));

same('edge base: dev', 'https://telemetry.dev.paypercut.net/', Environment::telemetryBaseUri('dev'));
same('edge base: stage', 'https://telemetry.stage.paypercut.net/', Environment::telemetryBaseUri('stage'));
same('edge base: production', 'https://telemetry.paypercut.io/', Environment::telemetryBaseUri('production'));
same('edge base: unknown yields no session', '', Environment::telemetryBaseUri('nonsense'));
same('edge base: unset yields no session', '', Environment::telemetryBaseUri(''));

foreach (array('production', 'stage', 'dev') as $environment) {
    $api = parse_url(Environment::apiBaseUri($environment), PHP_URL_HOST);
    $edge = parse_url(Environment::telemetryBaseUri($environment), PHP_URL_HOST);

    // A token minted for one environment is refused by every other edge, so the
    // two hosts must always name the same environment.
    check(
        'pairing: ' . $environment . ' hosts agree',
        $environment === 'production'
            ? ($api === 'api.paypercut.io' && $edge === 'telemetry.paypercut.io')
            : (strpos($api, '.' . $environment . '.paypercut.net') !== false
                && strpos($edge, '.' . $environment . '.paypercut.net') !== false)
    );
}

same('allowed base: rejects http', '', Environment::allowedPaypercutBase('http://api.paypercut.io/'));
same('allowed base: rejects lookalike suffix', '', Environment::allowedPaypercutBase('https://paypercut.io.evil.com/'));
same('allowed base: rejects prefixed host', '', Environment::allowedPaypercutBase('https://notpaypercut.io/'));
same('allowed base: rejects extra tld', '', Environment::allowedPaypercutBase('https://paypercut.io.co/'));
same('allowed base: accepts subdomain', 'https://telemetry.dev.paypercut.net/', Environment::allowedPaypercutBase('https://telemetry.dev.paypercut.net'));

// The order screen links a merchant straight at a payment record, so it has to
// follow the environment the payment was made in.
same('dashboard base: dev', 'https://dashboard.dev.paypercut.net/', Environment::dashboardBaseUri('dev'));
same('dashboard base: stage', 'https://dashboard.stage.paypercut.net/', Environment::dashboardBaseUri('stage'));
same('dashboard base: production', 'https://dashboard.paypercut.io/', Environment::dashboardBaseUri('production'));
same('dashboard base: unknown falls back to production', 'https://dashboard.paypercut.io/', Environment::dashboardBaseUri('nonsense'));

// --- The deny assertion -----------------------------------------------------

$denied_keys = array('api_client_secret', 'telemetry_token', 'api_key', 'nonce', 'authorization', 'webhook_secret', 'password', 'credential');

foreach ($denied_keys as $key) {
    check('deny key: ' . $key, Event::isDenied(array('attrs' => array($key => 'value'))));
}

$denied_values = array(
    'rejected ppc_live_store_secret',
    'key sk_test_abc rejected',
    'whsec_abcdef was wrong',
    'Bearer eyJhbGciOiJSUzI1NiJ9.payload'
);

foreach ($denied_values as $value) {
    check('deny value: ' . $value, Event::isDenied(array('attrs' => array('note' => $value))));
}

$permitted_values = array('disk_usage exceeded', 'backpack_pk_none missing', 'risk_free window elapsed');

foreach ($permitted_values as $value) {
    check('permit value: ' . $value, !Event::isDenied(array('attrs' => array('note' => $value))));
}

check('deny PAN: plain', Event::containsCardNumber('Card 4111111111111111 was declined'));
check('deny PAN: spaced', Event::containsCardNumber('card 4111 1111 1111 1111 declined'));
check('permit non-Luhn digits', !Event::containsCardNumber('transaction 1234567890123456 not found'));
check('permit timestamp', !Event::containsCardNumber('expired at 1787250271000'));
check('permit amount', !Event::containsCardNumber('amount 4250 refused'));

check(
    'deny literal secret anywhere in the value',
    Event::isDenied(array('attrs' => array('note' => 'call failed for shhh-very-secret-value')), array('shhh-very-secret-value'))
);

check(
    'empty credentials never match',
    !Event::isDenied(array('attrs' => array('note' => 'nothing to see')), array('', null))
);

// error is a top-level sibling of attrs, and error.stack nests one level deeper.
check(
    'deny reaches error.message',
    Event::isDenied(array('error' => array('message' => 'rejected sk_live_abc')))
);

check(
    'deny reaches error.stack (depth 2)',
    Event::isDenied(array('error' => array('stack' => array('boom ppc_live_key'))))
);

// --- The gate screens the envelope, not a named subset of it ----------------

/**
 * One copy of $envelope per leaf, with $poison substituted at that leaf.
 *
 * Walking the envelope rather than a list of field names is the point: a field
 * added to the wire shape later is poisoned here without anyone updating this
 * file, so it cannot ship unscreened.
 */
function poisonLeaves($value, $poison, $path = '')
{
    if (!is_array($value)) {
        return array(array('path' => $path, 'value' => $poison));
    }

    $cases = array();

    foreach ($value as $key => $child) {
        $child_path = $path === '' ? (string)$key : $path . '.' . $key;

        foreach (poisonLeaves($child, $poison, $child_path) as $leaf) {
            $copy = $value;
            $copy[$key] = $leaf['value'];

            $cases[] = array('path' => $leaf['path'], 'value' => $copy);
        }
    }

    return $cases;
}

function throwingCall()
{
    throw new RuntimeException('order lookup returned nothing');
}

try {
    throwingCall();
} catch (Exception $thrown) {
    // Caught so the envelope below carries a real stack.
}

// Every field a producer can populate, in one envelope.
$populated = Event::failure(
    'webhook.unresolved',
    'order_not_found',
    array('webhook' => 'payment_intent.succeeded', 'http_status' => 404),
    $thrown
)
    ->about(
        array(
            'order_ref' => 'OC-42',
            'payment_id' => 'pay_1',
            'payment_intent_id' => 'pi_1'
        )
    )
    ->because('no order carried this payment intent')
    ->envelope(0);

$store_secrets = array('sk_live_STORE_KEY', 'a-store-secret-in-no-known-format');

check('the fully populated envelope is itself clean', !EventQueue::screen($populated, $store_secrets));

$reached = array();

foreach (poisonLeaves($populated, 'placeholder') as $case) {
    $reached[] = $case['path'];
}

// The correlation fields are the ones the reviewer proved were never screened;
// an unauthenticated webhook body reaches payment_id at six call sites.
foreach (array('event', 'occurred_at', 'order_ref', 'payment_id', 'payment_intent_id', 'error.code', 'error.type', 'error.message', 'error.stack.0', 'attrs.webhook') as $field) {
    check('the screening walk reaches ' . $field, in_array($field, $reached, true));
}

// Widening the screen must not start dropping ordinary traffic: real Paypercut
// identifiers and a merchant's own order reference format have to survive it.
$real_ids = array(
    array('order_ref' => 'OC-2026/8891', 'payment_id' => 'pay_9RTvK2', 'payment_intent_id' => 'pi_9RTvK2'),
    array('order_ref' => '178', 'payment_id' => 'cs_apm_123', 'payment_intent_id' => 'pm_123')
);

foreach ($real_ids as $index => $ids) {
    check(
        'ordinary correlation ids still pass the widened screen (' . $index . ')',
        !EventQueue::screen(Event::of('payment.succeeded')->about($ids)->envelope(0), $store_secrets)
    );
}

$poisons = array(
    'pan' => '4111111111111111',
    'pan in prose' => 'card 4111 1111 1111 1111 declined',
    'paypercut key shape' => 'ppc_live_ABCDEF',
    'literal store api key' => 'sk_live_STORE_KEY',
    'literal store secret of unknown shape' => 'a-store-secret-in-no-known-format'
);

foreach ($poisons as $label => $poison) {
    foreach (poisonLeaves($populated, $poison) as $case) {
        check(
            'the gate screens ' . $case['path'] . ' for a ' . $label,
            EventQueue::screen($case['value'], $store_secrets)
        );
    }
}

// A field the gate cannot read as text is denied, not stepped over: that is what
// makes "every value in the envelope" hold for a shape nobody has written yet.
$store_secret = 'a-store-secret-in-no-known-format';

check(
    'a non-string scalar is screened too',
    EventQueue::screen(Event::of('test.event', array('n' => 4111111111111111))->envelope(0), $store_secrets)
);

check(
    'a structure deeper than the wire contract is denied',
    EventQueue::screen(
        array('event' => 'test.event', 'error' => array('stack' => array(array('leaked' => $store_secret)))),
        $store_secrets
    )
);

check(
    'a non-scalar leaf is denied',
    EventQueue::screen(array('event' => 'test.event', 'attrs' => array('o' => new stdClass())), $store_secrets)
);

// text() clamps before the gate runs, so a credential straddling the byte
// budget arrives as its own opening fragment with nothing left to contain it.
$clamped = Event::of('test.event', array('note' => str_repeat('x', 240) . $store_secret))->envelope(0);

same('the clamp really did cut the secret', 256, strlen($clamped['attrs']['note']));
check('the clamped remainder no longer contains the secret', strpos($clamped['attrs']['note'], $store_secret) === false);
check('a secret cut by the byte clamp is still denied', EventQueue::screen($clamped, $store_secrets));

// Widening must not start binning ordinary events.
check(
    'ordinary prose survives the fragment match',
    !EventQueue::screen(
        Event::of('payment.succeeded', array('mode' => 'hosted', 'note' => 'nothing to declare'))
            ->about(array('order_ref' => 'OC-2026/8891'))
            ->envelope(0),
        $store_secrets
    )
);

// --- Named constructors are the boundary ------------------------------------

$snapshot = Event::environmentSnapshot(
    array(
        'plugin_version' => '1.0.6',
        'payment_paypercut_api_key' => 'sk_live_should_never_travel',
        'webhook_secret' => 'whsec_should_never_travel'
    )
);

same('snapshot walks its own schema', array('plugin_version' => '1.0.6'), $snapshot->fields());

$configuration = Event::environmentConfiguration(
    array(
        'checkout_mode' => 'hosted',
        'api_client_secret' => 'sk_live_should_never_travel'
    )
);

same('configuration walks its own schema', array('checkout_mode' => 'hosted'), $configuration->fields());

$api_failure = Event::apiFailure(
    'api.request_failed',
    401,
    array(
        'trace_id' => 'da74bc',
        'error' => array(
            'type' => 'invalid_request_error',
            'code' => 'token_invalid',
            'message' => "The provided access token 'sk_test_abc' is invalid."
        )
    ),
    array('api_context' => 'checkout_session_create')
);

$envelope = $api_failure->envelope(0);

check('api failure drops upstream prose', !isset($envelope['error']['message']));
same('api failure keeps the error type', 'invalid_request_error', $envelope['error']['type']);
same('api failure keeps the error code', 'http_401', $envelope['error']['code']);
same('api failure carries api_code', 'token_invalid', $envelope['attrs']['api_code']);
same('api failure carries trace_id', 'da74bc', $envelope['attrs']['trace_id']);
same('api failure carries http_status', 401, $envelope['attrs']['http_status']);

// An event assembled from that response must survive the deny assertion, which
// is the whole point of dropping the message.
check(
    'api failure survives the deny assertion',
    !Event::isDenied(array('attrs' => $envelope['attrs'], 'error' => $envelope['error']))
);

// --- Platform prose never reaches the wire ----------------------------------

// OpenCart 3's mysqli adapter puts these on every exception it raises: the full
// SQL for a query error, and the credentials host for a connection failure.
$db_connect_error = new Exception('Error: Could not make a database link using ocuser@db-prod.merchant.internal!');
$db_query_error = new Exception("Error: Duplicate entry<br />Error No: 1062<br />INSERT INTO oc_order_history SET comment = 'Reason: customer changed their mind'");

foreach (array('connection' => $db_connect_error, 'query' => $db_query_error) as $label => $thrown_db) {
    $db_envelope = Event::failure('refund.failed', 'transport', array('has_reason' => true), $thrown_db)->envelope(0);
    $serialised = json_encode($db_envelope);

    check('failure() drops the platform message (' . $label . ')', !isset($db_envelope['error']['message']));
    same('failure() keeps the exception type (' . $label . ')', 'Exception', $db_envelope['error']['type']);
    check('no database credentials on the wire (' . $label . ')', strpos($serialised, 'ocuser@db-prod') === false);
    check('no SQL on the wire (' . $label . ')', strpos($serialised, 'INSERT INTO') === false);
    check('no refund reason text on the wire (' . $label . ')', strpos($serialised, 'changed their mind') === false);
}

// error_get_last() prose is the same exposure without a catch block: an uncaught
// DB exception arrives with the whole failing statement inlined.
$fatal = Event::fatal(
    "Uncaught PDOException: SQLSTATE[23000] in /home/merchant/public_html/x.php:9\nINSERT INTO oc_paypercut_webhook_log SET payload = '{\"card\":\"4111111111111111\"}'",
    '/home/merchant/public_html/x.php',
    9,
    E_ERROR
)->envelope(0);

same('fatal() classifies rather than quotes', 'uncaught_exception', $fatal['error']['message']);
same('fatal() keeps the uncaught class', 'PDOException', $fatal['error']['type']);
check('fatal() ships no payload', strpos(json_encode($fatal), '4111111111111111') === false);

same(
    'fatal() categorises a memory exhaustion',
    'memory_exhausted',
    Event::fatal('Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes)', '/x.php', 1, E_ERROR)->envelope(0)['error']['message']
);

same(
    'fatal() falls back to a fixed category',
    'fatal_error',
    Event::fatal('Call to a member function query() on null', '/x.php', 1, E_ERROR)->envelope(0)['error']['message']
);

// --- Bounding ---------------------------------------------------------------

same('text preserves UTF-8', 'Θέμα Ελλάδα', Event::text('Θέμα Ελλάδα'));
same('text strips control characters', 'ab', Event::text("a\x00b"));
check('text clamps on bytes', strlen(Event::text(str_repeat('a', 400))) === Event::MAX_TEXT_BYTES);
check('text clamps multibyte on bytes', strlen(Event::text(str_repeat('ま', 200))) <= Event::MAX_TEXT_BYTES);

same('identifier accepts a slug', 'dbg_abc.1:2-3', Event::identifier('dbg_abc.1:2-3'));
same('identifier drops an email', '', Event::identifier('jane@example.com'));
same('identifier drops an address', '', Event::identifier('12 Sunset Road'));
same('identifier drops an over-long value', '', Event::identifier(str_repeat('a', 65)));

$bounded = Event::of('test.event', array('flag' => false, 'count' => 7, 'nested' => array('a'), 'note' => 'ok'));
$fields = $bounded->fields();

same('booleans pass through intact', false, $fields['flag']);
same('ints pass through intact', 7, $fields['count']);
check('containers are dropped', !isset($fields['nested']));

$wide = array();

for ($i = 0; $i < 40; $i++) {
    $wide['key_' . $i] = $i;
}

check('attrs are capped', count(Event::of('test.event', $wide)->fields()) === Event::MAX_ATTRS);

// cleanAttrs() alone cannot hold the bound: failure() merges origin fields and
// apiFailure() adds four more on top of an already-capped set.
$full_attrs = array();

for ($i = 0; $i < Event::MAX_ATTRS; $i++) {
    $full_attrs['attr_' . $i] = $i;
}

$merged = Event::apiFailure(
    'api.request_failed',
    401,
    array('trace_id' => 'da74bc', 'error' => array('code' => 'token_invalid', 'param' => 'api_key_id')),
    $full_attrs
)->envelope(0);

check('the cap holds after the api-failure merge', count($merged['attrs']) === Event::MAX_ATTRS);

same('identifier rejects a trailing newline', '', Event::identifier("dbg_abc\n"));

// The deny assertion compares against the clamped value, so a credential
// straddling the byte budget must not leave its first characters behind.
$split_secret = str_repeat('a', 250) . ' zzzzstoresecretvalue';
$clamped = Event::text($split_secret);

same('the clamp cuts back to a boundary', str_repeat('a', 250), $clamped);
check('no fragment of the split token survives', strpos($clamped, 'zzzz') === false);

$empty = Event::of('test.event')->envelope(0);
check('empty attrs are omitted', !isset($empty['attrs']));
same('occurred_at is an RFC3339 string', '1970-01-01T00:00:00Z', $empty['occurred_at']);

$correlated = Event::of('test.event')->about(array('order_ref' => 'OC-42', 'payment_id' => 'pay_1'))->envelope(0);
same('correlation sits outside attrs', 'OC-42', $correlated['order_ref']);
check('correlation drops undeclared keys', !isset($correlated['checkout_id']));

// The webhook route accepts an unsigned delivery when no secret is configured,
// so the id it carries is attacker-controlled until it is shape-checked.
$hostile = Event::of('webhook.unresolved')->about(
    array(
        'payment_id' => 'card 4111 1111 1111 1111 declined',
        'payment_intent_id' => "pi_1\nSet-Cookie: x",
        'order_ref' => 'OC-42'
    )
)->envelope(0);

check('a payment id that is not identifier-shaped is dropped', !isset($hostile['payment_id']));
check('a payment intent id carrying a newline is dropped', !isset($hostile['payment_intent_id']));
same('the merchant order reference keeps its own format', 'OC-42', $hostile['order_ref']);

// --- The flusher's decision table -------------------------------------------

$decide = array(
    // status, retry_after, failures => outcome, end_session, retry_in, clears_batch
    array(202, 0, 0, 'accepted', false, 0, true),
    array(401, 0, 0, 'token_rejected', true, 0, true),
    array(413, 0, 3, 'split', false, 0, false),
    array(429, 30, 0, 'throttled', false, 30, false),
    array(429, 5000, 0, 'throttled', false, 900, false),
    array(429, 0, 0, 'throttled', false, 60, false),
    array(503, 0, 9, 'unready', false, 120, false),
    array(504, 0, 9, 'unready', false, 120, false),
    array(400, 0, 0, 'poison', false, 30, true),
    array(400, 0, 3, 'poison', true, 300, true),
    array(500, 0, 0, 'failed', false, 30, false),
    array(0, 0, 1, 'failed', false, 120, false),
    array(0, 0, 2, 'failed', false, 300, false),
    array(0, 0, 3, 'failed', true, 300, false)
);

foreach ($decide as $case) {
    list($status, $retry_after, $prior_failures, $outcome, $end_session, $retry_in, $clears_batch) = $case;

    $decision = Flusher::decide($status, $retry_after, $prior_failures);
    $label = 'decide(' . $status . ', ' . $retry_after . ', ' . $prior_failures . ')';

    same($label . ' outcome', $outcome, $decision['outcome']);
    same($label . ' end_session', $end_session, $decision['end_session']);
    same($label . ' retry_in', $retry_in, $decision['retry_in']);
    same($label . ' clears_batch', $clears_batch, $decision['clears_batch']);
}

// --- Queue bounds -----------------------------------------------------------

$envelopes = array();

for ($i = 0; $i < TelemetrySession::MAX_QUEUE_EVENTS + 25; $i++) {
    $envelopes[] = array('event' => 'test.event', 'occurred_at' => '1970-01-01T00:00:00Z', 'attrs' => array('n' => $i));
}

$capped = EventQueue::cap($envelopes);

same('cap keeps the newest', TelemetrySession::MAX_QUEUE_EVENTS, count($capped['envelopes']));
same('cap counts what it dropped', 25, $capped['dropped']);
same('cap drops the oldest first', 25, $capped['envelopes'][0]['attrs']['n']);

$split = EventQueue::splitBatch($envelopes, TelemetrySession::MAX_BATCH_BYTES, 10);

same('split honours the event cap', 10, count($split['batch']));
check('split neither drops nor reorders', array_merge($split['batch'], $split['remainder']) === $envelopes);

$one_huge = array(array('event' => 'test.event', 'attrs' => array('note' => str_repeat('a', 40000))));
$split = EventQueue::splitBatch($one_huge, TelemetrySession::MAX_BATCH_BYTES, 50);

same('split always takes at least one envelope', 1, count($split['batch']));

// --- The merchant-facing disclosure -----------------------------------------

$_ = array();
require $root . '/upload/admin/language/en-gb/extension/payment/paypercut_telemetry.php';
$strings = $_;

$doc = file_get_contents($root . '/docs/telemetry.md');
$normalise = function ($value) {
    return strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
};

$doc_normalised = $normalise($doc);

foreach (array('text_telemetry_shared', 'text_telemetry_not_shared', 'text_telemetry_key_use', 'text_telemetry_retention') as $key) {
    // The panel and the documented disclosure must not drift: a merchant
    // agreeing to one thing while the docs say another is a real problem.
    check(
        'disclosure in step with docs/telemetry.md: ' . $key,
        strpos($doc_normalised, $normalise($strings[$key])) !== false
    );
}

// --- The event catalogue stays honest ---------------------------------------

$sources = array(
    $root . '/upload/catalog/controller/extension/payment/paypercut.php',
    $root . '/upload/admin/controller/extension/payment/paypercut.php',
    $root . '/upload/admin/controller/extension/payment/paypercut_order.php',
    $root . '/upload/admin/controller/extension/payment/paypercut_telemetry.php'
);

$emitted = array();

foreach ($sources as $source) {
    preg_match_all("/Event::(?:of|failure|apiFailure)\\(\s*'([a-z_]+\\.[a-z_.]+)'/", file_get_contents($source), $matches);

    foreach ($matches[1] as $name) {
        $emitted[$name] = true;
    }
}

check('the catalogue is not empty', count($emitted) > 20);

foreach (array_keys($emitted) as $name) {
    // An event added at a call site nobody documented fails the build.
    check('documented in docs/telemetry.md: ' . $name, strpos($doc, '`' . $name . '`') !== false);
}

// Each of these decides whether a shopper's money became an order. Every one of
// them was silent before, which is why "it just did nothing" was unanswerable.
$outcome_paths = array(
    $root . '/upload/catalog/controller/extension/payment/paypercut.php',
    $root . '/upload/admin/controller/extension/payment/paypercut_order.php'
);

foreach ($outcome_paths as $path) {
    check('records outcomes: ' . basename($path), strpos(file_get_contents($path), 'EventRecorder::record(') !== false);
}

// --- The sent log is never stale --------------------------------------------

$session_source = file_get_contents($root . '/upload/system/library/paypercut/telemetry/telemetrysession.php');
$panel_source = file_get_contents($root . '/upload/admin/view/template/extension/payment/paypercut_telemetry.twig');

check('starting a session clears the log server-side', strpos($session_source, 'SentLog::clear();') !== false);
check('the rendered log block is addressable', strpos($panel_source, 'data-paypercut-log') !== false);

// The panel is rendered once per page load, so a merchant who starts without
// reloading would otherwise read the previous session's events under the new
// session's heading.
preg_match('/if \(started\) \{(.*?)\}/s', $panel_source, $started_branch);
check('the client drops the stale log on start', isset($started_branch[1]) && strpos($started_branch[1], 'dropSentLog()') !== false);

// --- Structural guards no unit test can reach -------------------------------

$queue_source = file_get_contents($root . '/upload/system/library/paypercut/telemetry/eventqueue.php');
$flusher_source = file_get_contents($root . '/upload/system/library/paypercut/telemetry/flusher.php');
$admin_source = file_get_contents($root . '/upload/admin/controller/extension/payment/paypercut.php');
$telemetry_source = file_get_contents($root . '/upload/admin/controller/extension/payment/paypercut_telemetry.php');

check(
    'the gate never screens a named subset again',
    strpos($queue_source, "array('attrs', 'error')") === false
);

check(
    'append refuses to rebuild the queue after teardown',
    strpos($queue_source, '!TelemetrySession::isActiveFast()') !== false
);

check(
    'the stored edge base is re-validated before a token is sent to it',
    strpos($flusher_source, 'Environment::allowedPaypercutBase(') !== false
);

// OpenCart resolves the permission route from the third path segment, and only
// `extension/payment/paypercut` is granted at install — an action on any other
// controller answers the panel's AJAX with the permission page.
foreach (array('telemetryStart', 'telemetryStop', 'telemetryStatus') as $action) {
    check('the permitted controller exposes ' . $action, strpos($admin_source, 'public function ' . $action . '(') !== false);
    check('the panel links the permitted route for ' . $action, strpos($telemetry_source, "'extension/payment/paypercut/" . $action . "'") !== false);
}

foreach (array('start', 'stop', 'status') as $action) {
    check(
        'the panel no longer links the unpermitted route for ' . $action,
        strpos($telemetry_source, "'extension/payment/paypercut_telemetry/" . $action . "'") === false
    );
}

// Twig runs with autoescape off in OpenCart 3, so every value in the panel is a
// raw sink until it is filtered.
preg_match_all('/\{\{\s*(session_id|trace_id|message|started_by_name)\s*\}\}/', $panel_source, $unescaped);
check('no unescaped panel value', empty($unescaped[0]));

// --- Result -----------------------------------------------------------------

echo PHP_EOL;

if (empty($failures)) {
    echo 'OK — ' . $assertions . ' assertions passed.' . PHP_EOL;
    exit(0);
}

echo count($failures) . ' of ' . $assertions . ' assertions failed.' . PHP_EOL;
exit(1);
