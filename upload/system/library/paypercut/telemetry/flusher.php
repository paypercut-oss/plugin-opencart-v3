<?php

namespace Paypercut\Telemetry;

/**
 * Delivers queued diagnostic events to the telemetry edge.
 *
 * Runs only from authenticated admin requests: the panel's status poll, the
 * Stop handler and the settings page render. Never from a storefront request,
 * never from the webhook.
 */
class Flusher
{
    /**
     * Backoff ladder applied after consecutive delivery failures, in seconds.
     */
    private static $backoff_seconds = array(30, 120, 300);

    private $client;

    public function __construct($client = null)
    {
        $this->client = $client === null ? new EdgeClient() : $client;
    }

    /**
     * Attempt to deliver at most one batch.
     *
     * @return bool Whether a delivery was attempted.
     */
    public function flushOnce()
    {
        if (!Context::isAuthenticatedAdmin()) {
            return false;
        }

        $record = TelemetrySession::record();

        if (!isset($record['status']) || $record['status'] !== 'active') {
            return false;
        }

        if ((int)(isset($record['expires_at']) ? $record['expires_at'] : 0) <= time()) {
            return false;
        }

        $runtime = TelemetrySession::runtime();

        if ((int)(isset($runtime['next_attempt_at']) ? $runtime['next_attempt_at'] : 0) > time()) {
            return false;
        }

        if (!TelemetrySession::claimFlushLock()) {
            return false;
        }

        try {
            $delivered = $this->deliver($record);
        } catch (\Exception $exception) {
            TelemetrySession::releaseFlushLock();

            return false;
        }

        TelemetrySession::releaseFlushLock();

        return $delivered;
    }

    private function deliver(array $record)
    {
        $max_events = self::maxEvents();

        // A parked batch always drains first, so a retry never reorders delivery.
        $batch = EventQueue::inflight();

        if (empty($batch)) {
            $batch = EventQueue::takeBatch(TelemetrySession::MAX_BATCH_BYTES, $max_events);
        }

        if (empty($batch)) {
            return false;
        }

        $token = TelemetrySession::token();

        if ($token === '') {
            TelemetrySession::end('token_lost');
            return false;
        }

        $edge_base = (string)(isset($record['edge_base']) ? $record['edge_base'] : '');

        if ($edge_base === '') {
            TelemetrySession::end('environment_changed');
            return false;
        }

        $client = self::clientIdentity();
        $split = EventQueue::splitBatch($batch, self::eventsBudget($client), $max_events);
        $head = $split['batch'];
        $tail = $split['remainder'];

        $body = json_encode(
            array(
                'client' => $client,
                'events' => $head
            )
        );

        if (!is_string($body)) {
            EventQueue::clearInflight();
            $this->countDropped(count($batch), 'encode_failed');

            return false;
        }

        $result = $this->client->send($edge_base, $token, $body);

        return $this->settle(
            (int)$result['status'],
            (int)$result['retry_after'],
            $head,
            $tail,
            isset($result['body']) && is_array($result['body']) ? $result['body'] : array()
        );
    }

    /**
     * Identifies the software that produced the batch.
     */
    private static function clientIdentity()
    {
        $version = defined('PAYPERCUT_PLUGIN_VERSION') ? (string)constant('PAYPERCUT_PLUGIN_VERSION') : '';
        $version = Event::text($version);

        return array(
            'platform' => 'opencart',
            'version' => $version === '' ? 'dev' : $version
        );
    }

    /**
     * Bytes left for the events array once the wrapper is paid for: the edge
     * caps the request body, not the events array.
     */
    private static function eventsBudget(array $client)
    {
        $wrapper = json_encode(
            array(
                'client' => $client,
                'events' => array()
            )
        );

        return TelemetrySession::MAX_BATCH_BYTES - (is_string($wrapper) ? strlen($wrapper) : 128);
    }

    /**
     * The event cap a batch must satisfy, as last advertised by the edge.
     *
     * Clamped on the way in: the edge may only ever make us more conservative.
     */
    private static function maxEvents()
    {
        $runtime = TelemetrySession::runtime();
        $events = (int)(isset($runtime['edge_max_events']) ? $runtime['edge_max_events'] : 0);

        return $events > 0
            ? max(1, min($events, TelemetrySession::MAX_BATCH_EVENTS))
            : TelemetrySession::MAX_BATCH_EVENTS;
    }

    /**
     * Decide what an edge response means, with no side effects.
     *
     * Kept pure and separate from settle() so the whole branch table —
     * including the give-up ladder — can be exercised without an edge, a
     * database or a running session.
     *
     * @param int $status      HTTP status, or 0 for a transport failure.
     * @param int $retry_after Value of the Retry-After header, 0 when absent.
     * @param int $failures    Consecutive failures BEFORE this attempt.
     */
    public static function decide($status, $retry_after, $failures)
    {
        $status = (int)$status;

        if ($status === 202) {
            return self::outcome('accepted', false, 0, true);
        }

        if ($status === 401) {
            // Never re-mint: every mint issues a token with a fresh expiry and
            // nothing can revoke one, so a re-mint would leave a credential
            // valid past the window the merchant agreed to.
            return self::outcome('token_rejected', true, 0, true);
        }

        if ($status === 413) {
            // Not a failure — the batch is being reshaped. A backoff rung would
            // punish a successful negotiation.
            return self::outcome('split', false, 0, false);
        }

        // Nothing in the edge answers 429; this covers infrastructure in front
        // of it. A hostile Retry-After must not park the session forever.
        if ($status === 429) {
            return self::outcome('throttled', false, $retry_after > 0 ? min($retry_after, 900) : 60, false);
        }

        if ($status === 503 || $status === 504) {
            // "My verification keys aren't ready" is a statement about the edge,
            // not about this token. Ending the session on a rolling deploy would
            // be a one-way door: there is no re-mint.
            return self::outcome('unready', false, 120, false);
        }

        $attempt = (int)$failures + 1;
        $give_up = $attempt >= TelemetrySession::MAX_CONSECUTIVE_SEND_FAILURES;
        $retry_in = self::$backoff_seconds[min($attempt, count(self::$backoff_seconds)) - 1];

        // Our bug, not the merchant's: drop the batch so the queue drains, but
        // still count it. An edge that rejects every batch we build makes the
        // session useless, and it should end rather than burn an hour silently
        // incrementing a dropped counter.
        if ($status === 400) {
            return self::outcome('poison', $give_up, $retry_in, true);
        }

        return self::outcome('failed', $give_up, $retry_in, false);
    }

    private static function outcome($outcome, $end_session, $retry_in, $clears_batch)
    {
        return array(
            'outcome' => $outcome,
            'end_session' => $end_session,
            'retry_in' => $retry_in,
            'clears_batch' => $clears_batch
        );
    }

    /**
     * Apply the edge's answer to the parked batch.
     *
     * @param array $head The events actually POSTed.
     * @param array $tail What stayed parked behind them.
     * @param array $body The edge's decoded response.
     */
    private function settle($status, $retry_after, array $head, array $tail, array $body)
    {
        $runtime = TelemetrySession::runtime();
        $failures = (int)(isset($runtime['consecutive_edge_failures']) ? $runtime['consecutive_edge_failures'] : 0);
        $decision = self::decide($status, $retry_after, $failures);
        $events = count($head);

        if ($decision['outcome'] === 'split') {
            return $this->resize($head, $tail, $body);
        }

        if ($decision['clears_batch']) {
            // Only the delivered head is settled; anything behind it stays parked.
            EventQueue::retainInflight($tail);
        }

        if ($decision['outcome'] === 'accepted') {
            // The edge drops malformed events individually and still answers
            // 202, so the counts it returns are the only honest accounting.
            $accepted = isset($body['accepted']) ? (int)$body['accepted'] : $events;
            $dropped = isset($body['dropped']) ? (int)$body['dropped'] : 0;

            SentLog::append($head);

            TelemetrySession::updateRuntime(
                array(
                    'events_sent' => (int)(isset($runtime['events_sent']) ? $runtime['events_sent'] : 0) + $accepted,
                    'consecutive_edge_failures' => 0,
                    'next_attempt_at' => 0,
                    'last_error' => ''
                )
            );

            if ($dropped > 0) {
                $this->countDropped($dropped, 'edge_dropped');
            }

            return true;
        }

        if ($decision['outcome'] === 'poison') {
            $this->countDropped($events, 'malformed_batch');
        }

        if ($decision['end_session']) {
            if ($decision['outcome'] !== 'token_rejected') {
                Context::audit(
                    'Telemetry: giving up on delivery',
                    array(
                        'status' => $status,
                        'failures' => $failures + 1
                    )
                );
            }

            TelemetrySession::end($decision['outcome'] === 'token_rejected' ? 'edge_rejected' : 'send_failed');

            return true;
        }

        $counts_as_failure = in_array($decision['outcome'], array('failed', 'poison'), true);

        TelemetrySession::updateRuntime(
            array(
                'consecutive_edge_failures' => $counts_as_failure ? $failures + 1 : $failures,
                'next_attempt_at' => time() + $decision['retry_in'] + mt_rand(0, 30),
                'last_error' => 'edge_' . $status
            )
        );

        return true;
    }

    /**
     * Answer a 413 by making the next batch smaller.
     *
     * The queue is never touched: the head stays parked and is re-split on the
     * next flush, one round trip later on purpose, because each attempt blocks
     * the merchant's browser for up to the edge timeout.
     */
    private function resize(array $head, array $tail, array $body)
    {
        if (count($head) === 1) {
            // A one-event batch cannot be split further, and `split` does not
            // advance the give-up ladder, so nothing else would break the loop.
            EventQueue::retainInflight($tail);
            $this->countDropped(1, 'oversize_event');

            // Name and size only: the envelope is the one thing not to log.
            Context::audit(
                'Telemetry: event too large to deliver',
                array(
                    'event' => (string)(isset($head[0]['event']) ? $head[0]['event'] : 'unknown'),
                    'bytes' => EventQueue::bytes($head)
                )
            );
        } else {
            // Halving guarantees progress on its own: a 413 raised by a proxy in
            // front of the edge carries no limits at all, and the edge's own
            // byte cap is larger than ours.
            $advertised = self::advertisedEvents($body);
            $halved = max(1, (int)floor(count($head) / 2));

            TelemetrySession::updateRuntime(
                array('edge_max_events' => $advertised > 0 ? min($advertised, $halved) : $halved)
            );
        }

        TelemetrySession::updateRuntime(
            array(
                'next_attempt_at' => 0,
                'last_error' => 'edge_413'
            )
        );

        return true;
    }

    /**
     * The event cap the edge named in its 413, or 0 when it named none.
     */
    private static function advertisedEvents(array $body)
    {
        $limits = isset($body['limits']) && is_array($body['limits']) ? $body['limits'] : array();

        return (int)(isset($limits['max_events']) ? $limits['max_events'] : 0);
    }

    private function countDropped($events, $reason)
    {
        Context::audit(
            'Telemetry: batch dropped',
            array(
                'events' => $events,
                'reason' => $reason
            )
        );

        $runtime = TelemetrySession::runtime();

        TelemetrySession::updateRuntime(
            array('events_dropped' => (int)(isset($runtime['events_dropped']) ? $runtime['events_dropped'] : 0) + $events)
        );
    }
}
