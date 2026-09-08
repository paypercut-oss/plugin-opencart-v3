<?php

namespace Paypercut\Telemetry;

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/support/environment.php';
require_once __DIR__ . '/telemetry/context.php';
require_once __DIR__ . '/telemetry/store.php';
require_once __DIR__ . '/telemetry/telemetrysession.php';
require_once __DIR__ . '/telemetry/event.php';
require_once __DIR__ . '/telemetry/eventqueue.php';
require_once __DIR__ . '/telemetry/eventrecorder.php';
require_once __DIR__ . '/telemetry/sentlog.php';
require_once __DIR__ . '/telemetry/fatalerrorwatch.php';

/**
 * Wires the telemetry units into an OpenCart request.
 *
 * The recording half is loaded on every request that can report — call sites on
 * the checkout path build events unconditionally and the recorder drops them
 * when no session is running. Minting and delivery are admin-only and their
 * classes are loaded on demand.
 */
class Bootstrap
{
    public static function boot($registry)
    {
        Context::boot($registry);

        if (!TelemetrySession::isActiveFast()) {
            return;
        }

        // Order matters: FatalErrorWatch must run after the recorder has
        // persisted whatever this request buffered.
        EventRecorder::register();
        FatalErrorWatch::register();
    }

    /**
     * Load the classes only an authenticated admin request uses.
     */
    public static function loadAdmin()
    {
        require_once __DIR__ . '/http/client.php';
        require_once __DIR__ . '/telemetry/minterrormapper.php';
        require_once __DIR__ . '/telemetry/tokenminter.php';
        require_once __DIR__ . '/telemetry/edgeclient.php';
        require_once __DIR__ . '/telemetry/flusher.php';
        require_once __DIR__ . '/telemetry/environmentsnapshot.php';
        require_once __DIR__ . '/telemetry/activeextensions.php';
    }
}
