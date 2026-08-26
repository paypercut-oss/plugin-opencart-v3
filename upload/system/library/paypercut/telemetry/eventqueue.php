<?php

namespace Paypercut\Telemetry;

/**
 * The buffered, best-effort store of diagnostic events awaiting delivery.
 *
 * Storefront requests only ever append here; delivery happens later, from an
 * authenticated admin request. Everything is capped, because a queue that can
 * grow without bound on a busy store is a denial of service against the store.
 */
class EventQueue
{
    /**
     * Append envelopes, dropping the oldest if that overflows the caps.
     */
    public static function append(array $envelopes)
    {
        $envelopes = self::assertSafe($envelopes);

        if (empty($envelopes)) {
            return;
        }

        $capped = self::cap(array_merge(self::read(TelemetrySession::QUEUE_KEY), $envelopes));

        self::write(TelemetrySession::QUEUE_KEY, $capped['envelopes']);

        // Counted only from admin requests: a storefront request must make at
        // most one write, and the queue write above is it.
        if ($capped['dropped'] > 0 && Context::isAuthenticatedAdmin()) {
            $runtime = TelemetrySession::runtime();

            TelemetrySession::updateRuntime(
                array('events_dropped' => (int)(isset($runtime['events_dropped']) ? $runtime['events_dropped'] : 0) + $capped['dropped'])
            );
        }
    }

    /**
     * The last gate before anything is persisted for delivery.
     *
     * Every producer funnels through here — the storefront recorder and the
     * admin-side lifecycle events alike — so the deny assertion cannot be
     * bypassed by adding a new call site. A tripped assertion drops the whole
     * event, not the offending field.
     */
    private static function assertSafe(array $envelopes)
    {
        if (empty($envelopes)) {
            return array();
        }

        $secrets = TelemetrySession::credentials();
        $safe = array();

        foreach ($envelopes as $envelope) {
            // `error` is a top-level sibling of `attrs`, so it has to be named
            // here or it bypasses the one gate every producer funnels through.
            $screened = array();

            foreach (array('attrs', 'error') as $field) {
                if (isset($envelope[$field]) && is_array($envelope[$field])) {
                    $screened[$field] = $envelope[$field];
                }
            }

            if (Event::isDenied($screened, $secrets)) {
                // The event name only — never the envelope.
                Context::audit(
                    'Telemetry: event dropped by the deny assertion',
                    array('event' => (string)(isset($envelope['event']) ? $envelope['event'] : 'unknown'))
                );

                continue;
            }

            $safe[] = $envelope;
        }

        return $safe;
    }

    /**
     * Enforce the queue caps, dropping the oldest entries first.
     */
    public static function cap(array $envelopes)
    {
        $dropped = 0;

        if (count($envelopes) > TelemetrySession::MAX_QUEUE_EVENTS) {
            $dropped = count($envelopes) - TelemetrySession::MAX_QUEUE_EVENTS;
            $envelopes = array_slice($envelopes, -TelemetrySession::MAX_QUEUE_EVENTS);
        }

        // Stop at one, mirroring splitBatch(): a single oversized envelope must
        // not empty the queue behind it.
        while (count($envelopes) > 1 && self::bytes($envelopes) > TelemetrySession::MAX_QUEUE_BYTES) {
            array_shift($envelopes);
            $dropped++;
        }

        return array(
            'envelopes' => $envelopes,
            'dropped' => $dropped
        );
    }

    /**
     * Split a batch off the front of the queue, within both edge bounds.
     *
     * Always takes at least one envelope: a single oversized envelope would
     * otherwise wedge the queue forever, and the edge rejecting it once is
     * cheaper than never draining.
     */
    public static function splitBatch(array $envelopes, $max_bytes, $max_events)
    {
        $batch = array();

        foreach ($envelopes as $envelope) {
            if (count($batch) >= $max_events) {
                break;
            }

            $candidate = array_merge($batch, array($envelope));

            if (!empty($batch) && self::bytes($candidate) > $max_bytes) {
                break;
            }

            $batch = $candidate;
        }

        return array(
            'batch' => $batch,
            'remainder' => array_slice($envelopes, count($batch))
        );
    }

    /**
     * Take a batch, shortening the stored queue immediately.
     *
     * The remainder is written back BEFORE the network call, and the batch is
     * parked under its own key. Holding the remainder across the request would
     * discard anything storefront requests appended while the POST was in
     * flight, and could resurrect an already-delivered batch.
     */
    public static function takeBatch($max_bytes, $max_events)
    {
        $split = self::splitBatch(self::read(TelemetrySession::QUEUE_KEY), $max_bytes, $max_events);

        if (empty($split['batch'])) {
            return array();
        }

        self::write(TelemetrySession::QUEUE_KEY, $split['remainder']);
        self::write(TelemetrySession::INFLIGHT_KEY, $split['batch']);

        return $split['batch'];
    }

    /**
     * A batch that was taken but whose delivery has not been settled.
     */
    public static function inflight()
    {
        return self::read(TelemetrySession::INFLIGHT_KEY);
    }

    public static function clearInflight()
    {
        Store::delete(TelemetrySession::INFLIGHT_KEY);
    }

    /**
     * Shorten the parked batch to what is left to deliver.
     *
     * The flusher may only ever SHORTEN inflight, never write the queue: the
     * flush lock excludes other flushers, but append() is an unlocked
     * read-modify-write from anonymous storefront requests, and takeBatch() has
     * already removed this batch from the queue.
     */
    public static function retainInflight(array $envelopes)
    {
        self::write(TelemetrySession::INFLIGHT_KEY, $envelopes);
    }

    public static function size()
    {
        return count(self::read(TelemetrySession::QUEUE_KEY)) + count(self::read(TelemetrySession::INFLIGHT_KEY));
    }

    public static function bytes(array $envelopes)
    {
        $json = json_encode($envelopes);

        return is_string($json) ? strlen($json) : 0;
    }

    private static function read($key)
    {
        $stored = Store::get($key);

        return is_array($stored) && isset($stored['envelopes']) && is_array($stored['envelopes'])
            ? $stored['envelopes']
            : array();
    }

    private static function write($key, array $envelopes)
    {
        if (empty($envelopes)) {
            Store::delete($key);
            return;
        }

        Store::put($key, array('envelopes' => $envelopes), self::ttl());
    }

    /**
     * Outlive the session slightly, so a final flush still finds its batch.
     */
    private static function ttl()
    {
        $record = TelemetrySession::record();
        $expires_at = (int)(isset($record['expires_at']) ? $record['expires_at'] : 0);

        return max(300, ($expires_at - time()) + 300);
    }
}
