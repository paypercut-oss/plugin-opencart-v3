<?php

namespace Paypercut\Telemetry;

/**
 * Builds the one-off environment snapshot sent when a session starts.
 *
 * Reads the store's own configuration only. Every value it collects is named
 * explicitly here and cast by Event::environmentSnapshot(); nothing is
 * harvested by walking a settings array, which is how a credential would end up
 * on the wire.
 */
class EnvironmentSnapshot
{
    public static function values()
    {
        $api_key = (string)Context::setting(TelemetrySession::API_KEY_SETTING, '');

        return array(
            'plugin_version' => defined('PAYPERCUT_PLUGIN_VERSION') ? (string)constant('PAYPERCUT_PLUGIN_VERSION') : 'dev',
            'oc_version' => defined('VERSION') ? (string)constant('VERSION') : '',
            'php_version' => PHP_VERSION,
            'theme_name' => (string)Context::setting('config_theme', ''),
            'theme_version' => '',
            'is_multistore' => self::isMultistore(),
            'is_ssl' => self::isSsl(),
            'checkout_mode' => (string)Context::setting('payment_paypercut_checkout_mode', 'hosted'),
            'order_status' => (string)Context::setting('payment_paypercut_order_status_id', ''),
            'google_pay' => (bool)Context::setting('payment_paypercut_google_pay', false),
            'apple_pay' => (bool)Context::setting('payment_paypercut_apple_pay', false),
            'logging_enabled' => (bool)Context::setting('payment_paypercut_logging', false),
            'statement_descriptor_set' => '' !== (string)Context::setting('payment_paypercut_statement_descriptor', ''),
            'payment_method_config_set' => '' !== (string)Context::setting('payment_paypercut_payment_method_config', ''),
            'connection_environment' => (string)Context::setting(TelemetrySession::ENVIRONMENT_SETTING, ''),
            'api_key_mode' => self::apiKeyMode($api_key),
            // Presence booleans derived from secret-bearing settings: the value
            // never travels, only whether one exists.
            'webhook_configured' => '' !== (string)Context::setting(TelemetrySession::WEBHOOK_SECRET_SETTING, ''),
            'payment_domain_registered' => '' !== (string)Context::setting('payment_paypercut_domain_id', ''),
            'card_enabled' => (bool)Context::setting('payment_paypercut_status', false),
            'store_currency' => (string)Context::setting('config_currency', '')
        );
    }

    private static function apiKeyMode($api_key)
    {
        if ($api_key === '') {
            return '';
        }

        if (strpos($api_key, 'sk_test') === 0) {
            return 'test';
        }

        if (strpos($api_key, 'sk_live') === 0) {
            return 'live';
        }

        return 'unknown';
    }

    private static function isMultistore()
    {
        $db = Context::db();

        if ($db === null) {
            return false;
        }

        try {
            $query = $db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "store`");
        } catch (\Exception $exception) {
            return false;
        }

        return (int)$query->row['total'] > 0;
    }

    private static function isSsl()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return defined('HTTPS_SERVER') && strpos((string)constant('HTTPS_SERVER'), 'https://') === 0;
    }
}
