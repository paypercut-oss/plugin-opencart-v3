<?php

namespace Paypercut\Telemetry;

/**
 * The registry the telemetry classes read OpenCart through.
 *
 * The telemetry units are static, like the reference implementation, so the
 * request's registry is handed to them once at boot rather than threaded
 * through every call.
 */
class Context
{
    private static $registry = null;

    private static $log = null;

    public static function boot($registry)
    {
        self::$registry = $registry;
    }

    public static function booted()
    {
        return self::$registry !== null;
    }

    public static function db()
    {
        return self::$registry === null ? null : self::$registry->get('db');
    }

    public static function config()
    {
        return self::$registry === null ? null : self::$registry->get('config');
    }

    /**
     * A stored setting value, or $default when the registry is not available.
     */
    public static function setting($key, $default = null)
    {
        $config = self::config();

        if ($config === null) {
            return $default;
        }

        $value = $config->get($key);

        return $value === null ? $default : $value;
    }

    /**
     * A translated string, falling back to the English copy compiled in.
     *
     * OpenCart's Language::get() returns the key itself when nothing is loaded,
     * which would put `error_telemetry_key_invalid` in front of a merchant.
     */
    public static function text($key, $default)
    {
        if (self::$registry === null) {
            return $default;
        }

        $language = self::$registry->get('language');

        if ($language === null) {
            return $default;
        }

        $value = $language->get($key);

        return ($value === $key || $value === '') ? $default : $value;
    }

    /**
     * Is this the admin application, serving a logged-in user?
     *
     * Deliberately stricter than "is this an admin route": DIR_CATALOG is
     * defined only by the admin entry point, and the session check keeps an
     * unauthenticated request off every path that mints or delivers.
     */
    public static function isAuthenticatedAdmin()
    {
        if (self::$registry === null || !defined('DIR_CATALOG')) {
            return false;
        }

        $user = self::$registry->get('user');

        return $user !== null && $user->isLogged();
    }

    /**
     * Write a log line whatever the merchant's logging preference is.
     *
     * Starting and stopping a session is an audit event: a store with logging
     * switched off must still leave a record that data left it.
     */
    public static function audit($message, array $context = array())
    {
        if (self::$log === null) {
            self::$log = new \Log('paypercut_telemetry.log');
        }

        self::$log->write($message . ' ' . json_encode($context));
    }

    /**
     * Test seam.
     */
    public static function reset()
    {
        self::$registry = null;
        self::$log = null;
    }
}
