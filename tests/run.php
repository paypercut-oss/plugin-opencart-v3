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

$empty = Event::of('test.event')->envelope(0);
check('empty attrs are omitted', !isset($empty['attrs']));
same('occurred_at is an RFC3339 string', '1970-01-01T00:00:00Z', $empty['occurred_at']);

$correlated = Event::of('test.event')->about(array('order_ref' => 'OC-42', 'payment_id' => 'pay_1'))->envelope(0);
same('correlation sits outside attrs', 'OC-42', $correlated['order_ref']);
check('correlation drops undeclared keys', !isset($correlated['checkout_id']));

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

// --- Result -----------------------------------------------------------------

echo PHP_EOL;

if (empty($failures)) {
    echo 'OK — ' . $assertions . ' assertions passed.' . PHP_EOL;
    exit(0);
}

echo count($failures) . ' of ' . $assertions . ' assertions failed.' . PHP_EOL;
exit(1);
