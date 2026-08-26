<?php

namespace Paypercut\Telemetry;

/**
 * Reports the fatal errors a debug session would otherwise never see.
 *
 * A fatal on the checkout page breaks our payment form whichever extension
 * raised it, and it never reaches a catch block — so the session sees nothing
 * at all unless the shutdown handler looks.
 */
class FatalErrorWatch
{
    /**
     * The levels that end a request. A warning is noise; these are the bug.
     */
    private static $fatal_levels = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

    private static $registered = false;

    public static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // Registered after EventRecorder so it still runs once that handler has
        // persisted whatever the request buffered.
        register_shutdown_function(array(__CLASS__, 'report'));
    }

    public static function report()
    {
        $error = error_get_last();

        if ($error === null || !in_array(isset($error['type']) ? $error['type'] : 0, self::$fatal_levels, true)) {
            return;
        }

        if (!Context::booted() || !TelemetrySession::isActiveFast()) {
            return;
        }

        $event = Event::fatal(
            (string)(isset($error['message']) ? $error['message'] : ''),
            (string)(isset($error['file']) ? $error['file'] : ''),
            (int)(isset($error['line']) ? $error['line'] : 0),
            (int)(isset($error['type']) ? $error['type'] : 0)
        );

        // The recorder's own shutdown handler has already run, so this writes
        // directly rather than buffering for a flush that will never come.
        EventQueue::append(array($event->envelope()));
    }

    /**
     * Test seam.
     */
    public static function reset()
    {
        self::$registered = false;
    }
}
