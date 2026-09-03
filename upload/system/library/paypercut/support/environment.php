<?php

namespace Paypercut\Support;

/**
 * Resolves Paypercut environment-specific service URLs.
 *
 * Both the API host and the telemetry edge host come from the single
 * `payment_paypercut_environment` setting: a token minted for one environment
 * is rejected by every other environment's edge with a 401 that looks exactly
 * like a forged token, so the two must never be resolved independently.
 */
class Environment
{
    const DEFAULT_ENVIRONMENT = 'production';

    /**
     * Core Paypercut API base URI for a stored connection environment.
     *
     * Falls back to production for an unknown value: this is the payment API,
     * and an existing store that has never chosen an environment must keep
     * working exactly as before.
     */
    public static function apiBaseUri($environment = '')
    {
        $base_uris = array(
            'dev' => 'https://api.dev.paypercut.net/',
            'stage' => 'https://api.stage.paypercut.net/',
            'production' => 'https://api.paypercut.io/'
        );

        if (isset($base_uris[$environment])) {
            return $base_uris[$environment];
        }

        return $base_uris[self::DEFAULT_ENVIRONMENT];
    }

    /**
     * Merchant Dashboard base URI for a stored connection environment.
     *
     * Falls back to production for the same reason apiBaseUri() does: a store
     * that never chose an environment keeps the link it has always had.
     */
    public static function dashboardBaseUri($environment = '')
    {
        $base_uris = array(
            'dev' => 'https://dashboard.dev.paypercut.net/',
            'stage' => 'https://dashboard.stage.paypercut.net/',
            'production' => 'https://dashboard.paypercut.io/'
        );

        if (isset($base_uris[$environment])) {
            return $base_uris[$environment];
        }

        return $base_uris[self::DEFAULT_ENVIRONMENT];
    }

    /**
     * Telemetry edge base URI for a stored connection environment.
     *
     * Unlike the API base this does NOT fall back to production: an unknown
     * environment must yield no session rather than a session whose events are
     * rejected as forged.
     */
    public static function telemetryBaseUri($environment = '')
    {
        $base_uris = array(
            'dev' => 'https://telemetry.dev.paypercut.net/',
            'stage' => 'https://telemetry.stage.paypercut.net/',
            'production' => 'https://telemetry.paypercut.io/'
        );

        if (!isset($base_uris[$environment])) {
            return '';
        }

        return self::allowedPaypercutBase($base_uris[$environment]);
    }

    /**
     * Accept a base URI only on an https Paypercut host.
     *
     * A credential travels on the mint request, so the destination is checked
     * rather than assumed. Returns '' for anything else, and callers treat that
     * as "no session".
     */
    public static function allowedPaypercutBase($url)
    {
        $url = trim((string)$url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';

        // The \z anchor is load-bearing: it rejects paypercut.io.evil.com,
        // notpaypercut.io and paypercut.io.co.
        if ($scheme !== 'https' || $host === '' || !preg_match('/(^|\.)paypercut\.(net|io)\z/D', $host)) {
            return '';
        }

        return rtrim($url, '/') . '/';
    }

    /**
     * The environments a merchant may choose between.
     */
    public static function choices()
    {
        return array('production', 'stage', 'dev');
    }

    /**
     * Normalise a stored setting value to a known environment.
     */
    public static function normalize($environment)
    {
        $environment = trim((string)$environment);

        return in_array($environment, self::choices(), true) ? $environment : self::DEFAULT_ENVIRONMENT;
    }
}
