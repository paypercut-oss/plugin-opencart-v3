<?php

namespace Paypercut\Telemetry;

/**
 * The only surface the rest of the module uses to report a diagnostic event.
 *
 * Call sites hand events here from anywhere, including anonymous checkout and
 * webhook requests. The contract those call sites rely on is that this method
 * is nearly free and never reaches the network: when no session is running it
 * reads one already-loaded setting and returns.
 */
class EventRecorder
{
    private static $buffer = array();

    private static $registered = false;

    /**
     * Arrange for the request's buffer to be written at shutdown.
     *
     * Registered at boot rather than on first use so that FatalErrorWatch,
     * registered after it, still runs last.
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        register_shutdown_function(array(__CLASS__, 'persist'));
    }

    /**
     * Buffer one event for later delivery.
     *
     * Never sends, and never tears a session down: that belongs to admin
     * requests, because this runs on the checkout path. The request's whole
     * contribution is one queue write at shutdown, however many events it
     * buffered.
     */
    public static function record(Event $event)
    {
        if (!Context::booted() || !TelemetrySession::isActiveFast()) {
            return;
        }

        // The deny assertion lives in EventQueue::append() so that it covers
        // every producer, including the admin-side lifecycle events.
        self::$buffer[] = $event->envelope();

        self::register();
    }

    /**
     * Write the request's buffered events to the queue, once, at shutdown.
     *
     * One capped write per request rather than one per event: concurrent
     * storefront requests read-modify-write the same row, so fewer writes means
     * fewer lost updates.
     */
    public static function persist()
    {
        if (empty(self::$buffer)) {
            return;
        }

        $buffer = self::$buffer;
        self::$buffer = array();

        EventQueue::append($buffer);
    }

    /**
     * Test seam: forget anything buffered by the current request.
     */
    public static function reset()
    {
        self::$buffer = array();
        self::$registered = false;
    }
}
