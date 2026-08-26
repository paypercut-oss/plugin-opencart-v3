<?php

namespace Paypercut\Telemetry;

/**
 * A local copy of the events this store actually delivered.
 *
 * The queue is emptied as it drains, so by the time anyone looks there is
 * nothing left to see: the panel could report "37 events sent" and offer no way
 * to find out what they were. Consent to send diagnostics is worth more when
 * the sender can inspect what left.
 *
 * Written only from authenticated admin requests, because that is the only
 * place the flusher runs.
 */
class SentLog
{
    const KEY = 'paypercut_telemetry_sent_log';

    /**
     * A session is an hour and a busy store delivers far more than this, so the
     * log is a tail rather than a transcript. The panel says so.
     */
    const MAX_ENTRIES = 100;

    const MAX_BYTES = 131072;

    /**
     * Record envelopes the edge accepted, newest last.
     */
    public static function append(array $envelopes)
    {
        if (empty($envelopes)) {
            return;
        }

        $entries = array_merge(self::all(), $envelopes);

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, -self::MAX_ENTRIES);
        }

        while (count($entries) > 1 && self::bytes($entries) > self::MAX_BYTES) {
            array_shift($entries);
        }

        Store::put(self::KEY, array('entries' => $entries));
    }

    public static function all()
    {
        $stored = Store::get(self::KEY);

        return is_array($stored) && isset($stored['entries']) && is_array($stored['entries'])
            ? $stored['entries']
            : array();
    }

    public static function clear()
    {
        Store::delete(self::KEY);
    }

    private static function bytes(array $entries)
    {
        $json = json_encode($entries);

        return is_string($json) ? strlen($json) : 0;
    }
}
