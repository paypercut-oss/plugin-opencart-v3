<?php

use Paypercut\Support\Environment;
use Paypercut\Telemetry\Bootstrap;
use Paypercut\Telemetry\Event;
use Paypercut\Telemetry\EventRecorder;
use Paypercut\Telemetry\Store;
use Paypercut\Telemetry\TelemetrySession;

class ControllerExtensionPaymentPaypercut extends Controller
{
    private $error = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        require_once DIR_SYSTEM . 'library/paypercut/bootstrap.php';

        Bootstrap::boot($registry);
    }

    /**
     * Resolve a Paypercut API endpoint for the store's chosen environment.
     *
     * The posted value wins while the settings form is being saved, so Test
     * Connection and domain registration use the environment being chosen.
     */
    private function apiUrl($path)
    {
        $environment = isset($this->request->post['payment_paypercut_environment'])
            ? $this->request->post['payment_paypercut_environment']
            : $this->config->get('payment_paypercut_environment');

        return Environment::apiBaseUri((string)$environment) . $path;
    }

    // Apple Pay domain verification file — see https://docs.paypercut.io/docs/accept-payments/apple-pay
    // Bundled at upload/system/library/paypercut/applepay/apple-developer-merchantid-domain-association.

    public function index()
    {
        $this->load->language('extension/payment/paypercut');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_paypercut', $this->request->post);

            $this->afterSettingsSaved();

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/paypercut', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/paypercut', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_paypercut_api_key'])) {
            $data['payment_paypercut_api_key'] = $this->request->post['payment_paypercut_api_key'];
        } else {
            $data['payment_paypercut_api_key'] = $this->config->get('payment_paypercut_api_key');
        }

        // Detect test/live mode from API key
        $api_key = isset($this->request->post['payment_paypercut_api_key']) ? $this->request->post['payment_paypercut_api_key'] : $this->config->get('payment_paypercut_api_key');
        $data['payment_paypercut_mode'] = $this->detectApiKeyMode($api_key);

        // Statement descriptor
        if (isset($this->request->post['payment_paypercut_statement_descriptor'])) {
            $data['payment_paypercut_statement_descriptor'] = $this->request->post['payment_paypercut_statement_descriptor'];
        } else {
            $data['payment_paypercut_statement_descriptor'] = $this->config->get('payment_paypercut_statement_descriptor');
        }

        // Wallet options
        if (isset($this->request->post['payment_paypercut_google_pay'])) {
            $data['payment_paypercut_google_pay'] = $this->request->post['payment_paypercut_google_pay'];
        } else {
            $data['payment_paypercut_google_pay'] = $this->config->get('payment_paypercut_google_pay');
        }

        if (isset($this->request->post['payment_paypercut_apple_pay'])) {
            $data['payment_paypercut_apple_pay'] = $this->request->post['payment_paypercut_apple_pay'];
        } else {
            $data['payment_paypercut_apple_pay'] = $this->config->get('payment_paypercut_apple_pay');
        }

        // Checkout mode
        if (isset($this->request->post['payment_paypercut_checkout_mode'])) {
            $data['payment_paypercut_checkout_mode'] = $this->request->post['payment_paypercut_checkout_mode'];
        } else {
            $data['payment_paypercut_checkout_mode'] = $this->config->get('payment_paypercut_checkout_mode') ?: 'hosted';
        }

        // Connection environment
        if (isset($this->request->post['payment_paypercut_environment'])) {
            $data['payment_paypercut_environment'] = $this->request->post['payment_paypercut_environment'];
        } else {
            $data['payment_paypercut_environment'] = Environment::normalize($this->config->get('payment_paypercut_environment'));
        }

        $data['environment_choices'] = array();

        foreach (Environment::choices() as $environment) {
            $data['environment_choices'][] = array(
                'value' => $environment,
                'label' => $this->language->get('text_environment_' . $environment)
            );
        }

        // Webhook URL
        $data['payment_paypercut_webhook_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/paypercut/webhook';

        // Check webhook status
        $data['webhook_status'] = $this->checkWebhookStatus();

        // Apple Pay domain verification file status (used by the Apple Pay toggle row)
        $data['applepay_domain_status'] = $this->checkApplePayDomainFile();

        // Payment method configuration
        if (isset($this->request->post['payment_paypercut_payment_method_config'])) {
            $data['payment_paypercut_payment_method_config'] = $this->request->post['payment_paypercut_payment_method_config'];
        } else {
            $data['payment_paypercut_payment_method_config'] = $this->config->get('payment_paypercut_payment_method_config');
        }

        // Load available payment method configurations
        $data['payment_method_configs'] = array();
        if (!empty($api_key)) {
            $configs = $this->getPaymentMethodConfigurations();
            if ($configs) {
                $data['payment_method_configs'] = $configs;
            }
        }

        if (isset($this->request->post['payment_paypercut_order_status_id'])) {
            $data['payment_paypercut_order_status_id'] = $this->request->post['payment_paypercut_order_status_id'];
        } else {
            $configured_status = $this->config->get('payment_paypercut_order_status_id');
            // Default to "Processing" status if not configured
            $data['payment_paypercut_order_status_id'] = $configured_status ? $configured_status : $this->getProcessingOrderStatusId();
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_paypercut_status'])) {
            $data['payment_paypercut_status'] = $this->request->post['payment_paypercut_status'];
        } else {
            $data['payment_paypercut_status'] = $this->config->get('payment_paypercut_status');
        }

        if (isset($this->request->post['payment_paypercut_sort_order'])) {
            $data['payment_paypercut_sort_order'] = $this->request->post['payment_paypercut_sort_order'];
        } else {
            $data['payment_paypercut_sort_order'] = $this->config->get('payment_paypercut_sort_order');
        }

        // Logging enabled
        if (isset($this->request->post['payment_paypercut_logging'])) {
            $data['payment_paypercut_logging'] = $this->request->post['payment_paypercut_logging'];
        } else {
            $data['payment_paypercut_logging'] = $this->config->get('payment_paypercut_logging');
        }

        // Check currency support
        $store_currency = $this->getStoreCurrency();
        $data['store_currency'] = $store_currency;
        $data['currency_supported'] = $this->isCurrencySupported($store_currency);

        if (!$data['currency_supported']) {
            $data['error_currency'] = sprintf($this->language->get('error_unsupported_currency'), $store_currency);
        } else {
            $data['error_currency'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Add user token for AJAX requests
        $data['user_token'] = $this->session->data['user_token'];

        $data['telemetry_panel'] = $this->load->controller('extension/payment/paypercut_telemetry/panel');

        // Language strings the template reads directly.
        $data = array_merge($this->language->all(), $data);

        $this->response->setOutput($this->load->view('extension/payment/paypercut', $data));
    }

    /**
     * Follow a settings save through: the record may now describe a connection
     * the store no longer has, and a live session's configuration snapshot is
     * out of date the moment a setting changes.
     */
    private function afterSettingsSaved()
    {
        // The request's config was loaded before the save, so the snapshot
        // below would otherwise describe the settings the merchant replaced.
        foreach ($this->request->post as $key => $value) {
            if (strpos($key, 'payment_paypercut_') === 0) {
                $this->config->set($key, $value);
            }
        }

        // Reaped first: a saved API key or environment change ends the session
        // here, and an event buffered before that would only be persisted after
        // the teardown that was meant to leave nothing behind.
        TelemetrySession::flushMemo();
        TelemetrySession::reap();

        if (!TelemetrySession::isActiveFast()) {
            return;
        }

        Bootstrap::loadAdmin();

        EventRecorder::record(
            Event::of(
                'connection.validated',
                array(
                    'source' => 'settings_save',
                    'is_bnpl' => false,
                    'environment' => (string)$this->config->get('payment_paypercut_environment')
                )
            )
        );

        // A session opened with one configuration snapshot; without this, a
        // setting changed mid-session is read against the snapshot taken before
        // it and the timeline lies.
        EventRecorder::record(Event::environmentConfiguration(\Paypercut\Telemetry\EnvironmentSnapshot::values()));
    }

    /**
     * The environment this request is acting on: the posted one while the form
     * is being saved, the stored one otherwise.
     */
    private function environmentForRequest()
    {
        return isset($this->request->post['payment_paypercut_environment'])
            ? $this->request->post['payment_paypercut_environment']
            : (string)$this->config->get('payment_paypercut_environment');
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/paypercut')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_paypercut_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        // Ensure payment method domain is registered for wallet payments
        if (!empty($this->request->post['payment_paypercut_api_key'])) {
            $domain_status = $this->ensurePaymentMethodDomain();
            if (!$domain_status['success']) {
                // Don't block saving, just show a warning
                $this->session->data['warning'] = 'Settings saved, but domain registration failed: ' . $domain_status['message'] . '. Wallet payment methods (Apple Pay, Google Pay) may not work until the domain is properly registered in your Paypercut dashboard.';
            }

            // Re-deploy the Apple Pay domain association file in case the upgrade path
            // skipped install() or the file was removed manually. Idempotent.
            $applepay_deploy = $this->deployApplePayDomainAssociation();
            if (!$applepay_deploy['success']) {
                $existing_warning = isset($this->session->data['warning']) ? $this->session->data['warning'] . ' ' : '';
                $this->session->data['warning'] = $existing_warning . 'Apple Pay domain verification file could not be deployed: ' . $applepay_deploy['message'] . ' Apple Pay will not work until this is resolved.';
            }
        }

        return !$this->error;
    }

    private function detectApiKeyMode($api_key)
    {
        if (empty($api_key)) {
            return '';
        }

        // Paypercut uses sk_test prefix for test keys and sk_live for live keys
        if (strpos($api_key, 'sk_test') === 0) {
            return 'test';
        } elseif (strpos($api_key, 'sk_live') === 0) {
            return 'live';
        }

        return 'unknown';
    }

    /**
     * Check if the provided currency is supported by Paypercut
     */
    private function isCurrencySupported($currency_code)
    {
        $supported_currencies = array('BGN', 'DKK', 'SEK', 'NOK', 'GBP', 'EUR', 'USD', 'CHF', 'CZK', 'HUF', 'PLN', 'RON');
        return in_array(strtoupper($currency_code), $supported_currencies);
    }

    /**
     * Get the store's default currency
     */
    private function getStoreCurrency()
    {
        return $this->config->get('config_currency');
    }

    /**
     * Get the order status ID for "Processing" status
     * Looks up the status by name to avoid hardcoding the ID
     */
    private function getProcessingOrderStatusId()
    {
        $query = $this->db->query("
            SELECT order_status_id 
            FROM `" . DB_PREFIX . "order_status` 
            WHERE name = 'Processing' 
            AND language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row['order_status_id'];
        }

        // Fallback to ID 2 if "Processing" status not found
        return 2;
    }

    private function checkWebhookStatus()
    {
        $api_key = $this->config->get('payment_paypercut_api_key');

        if (empty($api_key)) {
            return array(
                'configured' => false,
                'message' => 'Please configure your API key first'
            );
        }

        $webhook_url = HTTPS_CATALOG . 'index.php?route=extension/payment/paypercut/webhook';
        $webhook_id = $this->config->get('payment_paypercut_webhook_id');

        // If we have a stored webhook ID, verify it still exists
        if ($webhook_id) {
            $webhook = $this->getWebhook($webhook_id);
            if ($webhook && $webhook['url'] === $webhook_url && $webhook['status'] === 'enabled') {
                return array(
                    'configured' => true,
                    'webhook_id' => $webhook_id,
                    'message' => 'Webhook is configured and active',
                    'enabled_events' => $webhook['enabled_events']
                );
            }
        }

        // Check if webhook exists but we don't have the ID stored
        $existing_webhook = $this->findWebhookByUrl($webhook_url);
        if ($existing_webhook) {
            // Store the webhook ID
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_paypercut');
            $settings['payment_paypercut_webhook_id'] = $existing_webhook['id'];
            $this->model_setting_setting->editSetting('payment_paypercut', $settings);

            return array(
                'configured' => true,
                'webhook_id' => $existing_webhook['id'],
                'message' => 'Webhook found and linked',
                'enabled_events' => $existing_webhook['enabled_events']
            );
        }

        return array(
            'configured' => false,
            'message' => 'Webhook not configured'
        );
    }

    private function getWebhook($webhook_id)
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = $this->apiUrl('v1/webhooks/' . $webhook_id);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            return json_decode($response, true);
        }

        return null;
    }

    private function findWebhookByUrl($webhook_url)
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = $this->apiUrl('v1/webhooks');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if (isset($result['items'])) {
                foreach ($result['items'] as $webhook) {
                    if ($webhook['url'] === $webhook_url) {
                        return $webhook;
                    }
                }
            }
        } else {
            EventRecorder::record(
                Event::failure('settings.webhooks_unreadable', 'lookup_failed', array('http_status' => (int)$http_code))
            );
        }

        return null;
    }

    public function createWebhook()
    {
        $this->load->language('extension/payment/paypercut');

        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/payment/paypercut')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $api_key = $this->config->get('payment_paypercut_api_key');

            if (empty($api_key)) {
                $json['error'] = 'API key not configured';
            } else {
                $webhook_url = HTTPS_CATALOG . 'index.php?route=extension/payment/paypercut/webhook';

                // Check if webhook already exists
                $existing = $this->findWebhookByUrl($webhook_url);
                if ($existing) {
                    $json['error'] = 'Webhook already exists for this URL';
                    $json['webhook_id'] = $existing['id'];

                    EventRecorder::record(
                        Event::failure('connection.webhook_registration_failed', 'already_exists', array('source' => 'settings'))
                    );
                } else {
                    $api_url = $this->apiUrl('v1/webhooks');

                    // Create webhook with checkout_session.completed event enabled
                    $payload = array(
                        'name' => 'OpenCart - ' . HTTP_CATALOG,
                        'url' => $webhook_url,
                        'enabled_events' => array('checkout_session.completed')
                    );

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Authorization: Bearer ' . $api_key,
                        'Content-Type: application/json'
                    ));

                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($http_code == 201 || $http_code == 200) {
                        $result = json_decode($response, true);

                        // Store webhook ID and secret
                        $this->load->model('setting/setting');
                        $settings = $this->model_setting_setting->getSetting('payment_paypercut');
                        $settings['payment_paypercut_webhook_id'] = $result['id'];
                        $settings['payment_paypercut_webhook_secret'] = $result['secret'];
                        $this->model_setting_setting->editSetting('payment_paypercut', $settings);

                        $json['success'] = 'Webhook created successfully';
                        $json['webhook_id'] = $result['id'];

                        EventRecorder::record(Event::of('webhook.registered'));
                        EventRecorder::record(Event::of('connection.webhook_registered', array('source' => 'settings')));
                    } else {
                        $error_data = json_decode($response, true);
                        $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Failed to create webhook';

                        // The API quotes submitted input back, so only the
                        // status travels, never its prose.
                        EventRecorder::record(
                            Event::apiFailure('webhook.registration_failed', $http_code, is_array($error_data) ? $error_data : array())
                        );
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteWebhook()
    {
        $this->load->language('extension/payment/paypercut');

        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/payment/paypercut')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $webhook_id = $this->config->get('payment_paypercut_webhook_id');

            if (empty($webhook_id)) {
                $json['error'] = 'No webhook configured';
            } else {
                $api_key = $this->config->get('payment_paypercut_api_key');
                $api_url = $this->apiUrl('v1/webhooks/' . $webhook_id);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ));

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200) {
                    // Remove webhook ID from settings
                    $this->load->model('setting/setting');
                    $settings = $this->model_setting_setting->getSetting('payment_paypercut');
                    unset($settings['payment_paypercut_webhook_id']);
                    unset($settings['payment_paypercut_webhook_secret']);
                    $this->model_setting_setting->editSetting('payment_paypercut', $settings);

                    $json['success'] = 'Webhook deleted successfully';

                    EventRecorder::record(Event::of('webhook.deleted'));
                } else {
                    $json['error'] = 'Failed to delete webhook';

                    EventRecorder::record(
                        Event::failure('webhook.delete_failed', 'rejected', array('http_status' => (int)$http_code))
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Ensure payment method domain is registered for wallet payments
     */
    private function ensurePaymentMethodDomain()
    {
        $api_key = $this->config->get('payment_paypercut_api_key');

        if (empty($api_key)) {
            return array('success' => false, 'message' => 'API key not configured');
        }

        // Extract domain from catalog URL
        $domain = $this->extractDomain(HTTPS_CATALOG);

        if (empty($domain)) {
            return array('success' => false, 'message' => 'Could not extract domain from store URL');
        }

        // Check if domain is already registered
        $existing_domain = $this->getPaymentMethodDomain($domain);

        if ($existing_domain) {
            // Domain exists, check if it's enabled
            if ($existing_domain['enabled']) {
                return array(
                    'success' => true,
                    'message' => 'Domain already registered and enabled',
                    'domain_id' => $existing_domain['id']
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Domain registered but not enabled. Please verify domain ownership in Paypercut Dashboard.'
                );
            }
        }

        // Register the domain
        return $this->registerPaymentMethodDomain($domain);
    }

    /**
     * Extract domain name from URL
     */
    private function extractDomain($url)
    {
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : '';
    }

    /**
     * Get payment method domain from Paypercut
     */
    private function getPaymentMethodDomain($domain_name)
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = $this->apiUrl('v1/payment_method_domains');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if (isset($result['items'])) {
                foreach ($result['items'] as $domain) {
                    if ($domain['domain_name'] === $domain_name) {
                        return $domain;
                    }
                }
            }
        } else {
            EventRecorder::record(
                Event::failure('settings.payment_domains_unreadable', 'lookup_failed', array('http_status' => (int)$http_code))
            );
        }

        return null;
    }

    /**
     * Register payment method domain with Paypercut
     */
    private function registerPaymentMethodDomain($domain_name)
    {
        $api_key = $this->config->get('payment_paypercut_api_key');
        $api_url = $this->apiUrl('v1/payment_method_domains');

        $payload = array(
            'domain_name' => $domain_name
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 201 || $http_code == 200) {
            $result = json_decode($response, true);

            // Store domain ID for reference
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_paypercut');
            $settings['payment_paypercut_domain_id'] = $result['id'];
            $this->model_setting_setting->editSetting('payment_paypercut', $settings);

            EventRecorder::record(Event::of('payment_domain.registered'));
            EventRecorder::record(Event::of('connection.payment_domain_registered', array('source' => 'settings_save')));

            return array(
                'success' => true,
                'message' => 'Domain registered successfully. Verification may be required.',
                'domain_id' => $result['id'],
                'enabled' => isset($result['enabled']) ? $result['enabled'] : false
            );
        } else {
            $error_data = json_decode($response, true);
            $error_message = 'Failed to register domain';

            // Provide more specific error messages
            if ($http_code == 403) {
                $error_message = 'Permission denied (403). The API key may not have access to register domains, or the domain may already be registered in another account.';
            } elseif ($http_code == 400) {
                $error_message = 'Invalid domain name (400). Please check your store URL configuration.';
            } elseif ($http_code == 409) {
                $error_message = 'Domain already exists (409). Please check your Paypercut dashboard.';
            } elseif (isset($error_data['error']['message'])) {
                $error_message = $error_data['error']['message'];
            } elseif (isset($error_data['message'])) {
                $error_message = $error_data['message'];
            }

            // Log the error for debugging
            $this->log->write('Paypercut domain registration failed: HTTP ' . $http_code . ' - ' . $error_message . ' | Response: ' . $response);

            EventRecorder::record(
                Event::apiFailure('payment_domain.registration_failed', $http_code, is_array($error_data) ? $error_data : array())
            );
            EventRecorder::record(
                Event::failure('connection.payment_domain_registration_failed', 'rejected', array('source' => 'settings_save', 'http_status' => (int)$http_code))
            );

            return array(
                'success' => false,
                'message' => $error_message . ' (HTTP ' . $http_code . ')',
                'http_code' => $http_code
            );
        }
    }

    /**
     * Test API connection and get account information
     */
    public function testConnection()
    {
        $json = array();

        try {
            // Simple test first
            $api_key = isset($this->request->post['api_key']) ? trim($this->request->post['api_key']) : '';

            if (empty($api_key)) {
                $json['error'] = 'API key is required';
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }

            // Test connection by verifying account
            $api_url = $this->apiUrl('v1/account');

            $ch = curl_init();

            if ($ch === false) {
                $json['error'] = 'cURL initialization failed';
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }

            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ));

            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curl_error) {
                $json['error'] = 'Connection error: ' . $curl_error;
            } elseif ($response === false) {
                $json['error'] = 'Failed to execute request';
            } elseif ($http_code == 200) {
                $result = json_decode($response, true);

                // Detect mode using the detectApiKeyMode function
                $mode = $this->detectApiKeyMode($api_key);

                EventRecorder::record(
                    Event::of(
                        'connection.tested',
                        array(
                            'is_bnpl' => false,
                            'ok' => true,
                            'environment' => (string)$this->environmentForRequest(),
                            'api_key_mode' => (string)$mode
                        )
                    )
                );

                $json['success'] = true;
                $json['message'] = 'Connection successful!';
                $json['mode'] = $mode;
                if (isset($result['business_name'])) {
                    $json['account_name'] = $result['business_name'];
                }
            } elseif ($http_code == 401) {
                $json['error'] = 'Authentication failed. Please check your API key.';

                EventRecorder::record(
                    Event::failure(
                        'connection.tested',
                        'credentials_rejected',
                        array('is_bnpl' => false, 'ok' => false, 'http_status' => 401)
                    )
                );
            } elseif ($http_code == 0) {
                $json['error'] = 'Cannot connect to Paypercut API. Check your server\'s internet connection and SSL certificates.';
            } else {
                $error_data = json_decode($response, true);
                $json['error'] = isset($error_data['message']) ? $error_data['message'] : 'Connection failed with HTTP ' . $http_code . '. Response: ' . substr($response, 0, 100);
            }
        } catch (Exception $e) {
            $json['error'] = 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();

            // A rejected credential is quoted back in the message but never in
            // the class, so only the type travels.
            EventRecorder::record(
                Event::failure('connection.validation_failed', 'credentials_rejected', array('source' => 'test_connection', 'is_bnpl' => false))
                    ->because('validation threw ' . Event::shortClassName($e))
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get payment method configurations from Paypercut
     */
    private function getPaymentMethodConfigurations()
    {
        $api_key = $this->config->get('payment_paypercut_api_key');

        if (empty($api_key)) {
            return array();
        }

        $api_url = $this->apiUrl('v1/payment-configs');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            return isset($result['items']) ? $result['items'] : array();
        }

        return array();
    }

    /**
     * Deploy the Apple Pay domain association file from the bundled location
     * to the storefront's webroot at .well-known/apple-developer-merchantid-domain-association.
     * Never throws — returns a structured result so callers can surface a warning.
     */
    private function deployApplePayDomainAssociation()
    {
        $source = DIR_SYSTEM . 'library/paypercut/applepay/apple-developer-merchantid-domain-association';
        // DIR_APPLICATION is <webroot>/admin/ in OpenCart 3.x admin context; its parent is the
        // storefront webroot that HTTPS_CATALOG serves. (OpenCart 3.x has no DIR_OPENCART constant.)
        $well_known_dir = dirname(DIR_APPLICATION) . '/.well-known/';
        $destination = $well_known_dir . 'apple-developer-merchantid-domain-association';

        if (!is_file($source)) {
            $this->log->write('Paypercut Apple Pay file deploy: bundled file missing at ' . $source);
            return array(
                'success' => false,
                'message' => 'Verification file not found in plugin package at ' . $source . '.',
                'path' => $destination
            );
        }

        if (!is_dir($well_known_dir)) {
            if (!@mkdir($well_known_dir, 0755, true) && !is_dir($well_known_dir)) {
                $this->log->write('Paypercut Apple Pay file deploy: failed to create directory ' . $well_known_dir);
                return array(
                    'success' => false,
                    'message' => 'Could not create .well-known/ directory at ' . $well_known_dir . '. Check filesystem permissions on the OpenCart root.',
                    'path' => $destination
                );
            }
        }

        if (!@copy($source, $destination)) {
            $this->log->write('Paypercut Apple Pay file deploy: copy failed from ' . $source . ' to ' . $destination);
            return array(
                'success' => false,
                'message' => 'Could not write verification file to ' . $destination . '. Check filesystem permissions on the OpenCart root.',
                'path' => $destination
            );
        }

        @chmod($destination, 0644);

        return array(
            'success' => true,
            'message' => 'Apple Pay verification file deployed.',
            'path' => $destination
        );
    }

    /**
     * Self-test: confirm the verification file is on disk and reachable over HTTPS at the storefront URL.
     */
    private function checkApplePayDomainFile()
    {
        $destination = dirname(DIR_APPLICATION) . '/.well-known/apple-developer-merchantid-domain-association';
        $public_url = HTTPS_CATALOG . '.well-known/apple-developer-merchantid-domain-association';

        $result = array(
            'public_url' => $public_url,
            'local_path' => $destination,
            'state' => 'missing',
            'message' => ''
        );

        if (!is_file($destination)) {
            $result['message'] = 'Verification file is not deployed. Save settings or reinstall the extension to deploy.';
            return $result;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $public_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code === 0) {
            $result['state'] = 'unreachable';
            $result['message'] = 'File is on disk but the URL could not be reached from this server' . ($curl_error ? ' (' . $curl_error . ')' : '') . '. The merchant\'s browser may still reach it; verify in a browser.';
            return $result;
        }

        if ($http_code !== 200) {
            $result['state'] = 'http_error';
            $result['message'] = 'File is on disk but the URL returned HTTP ' . $http_code . '. The web server may be blocking the .well-known/ directory or rewriting the URL.';
            return $result;
        }

        $result['state'] = 'ok';
        $result['message'] = 'Apple Pay verification file is deployed and reachable.';
        return $result;
    }

    /**
     * Install method - Called when extension is installed
     * Creates database tables and registers events
     */
    public function install()
    {
        // Create database tables
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_customer` (
                `paypercut_customer_id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `paypercut_id` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`paypercut_customer_id`),
                UNIQUE KEY `customer_id` (`customer_id`),
                UNIQUE KEY `paypercut_id` (`paypercut_id`),
                KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_transaction` (
                `paypercut_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `payment_id` varchar(255) NOT NULL,
                `payment_intent` varchar(255) DEFAULT NULL,
                `payment_link_id` varchar(255) DEFAULT NULL,
                `checkout_id` varchar(255) DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `paypercut_customer_id` varchar(255) DEFAULT NULL,
                `amount` decimal(15,4) NOT NULL,
                `currency` varchar(3) NOT NULL,
                `status` varchar(50) NOT NULL,
                `payment_method_type` varchar(50) DEFAULT NULL,
                `payment_method_details` text,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`paypercut_transaction_id`),
                UNIQUE KEY `payment_id` (`payment_id`),
                KEY `order_id` (`order_id`),
                KEY `customer_id` (`customer_id`),
                KEY `checkout_id` (`checkout_id`),
                KEY `payment_intent` (`payment_intent`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_refund` (
                `paypercut_refund_id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `transaction_id` int(11) NOT NULL,
                `payment_id` varchar(255) NOT NULL,
                `refund_id` varchar(255) NOT NULL,
                `amount` decimal(15,4) NOT NULL,
                `currency` varchar(3) NOT NULL,
                `reason` varchar(255) DEFAULT NULL,
                `status` varchar(50) NOT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`paypercut_refund_id`),
                UNIQUE KEY `refund_id` (`refund_id`),
                KEY `order_id` (`order_id`),
                KEY `transaction_id` (`transaction_id`),
                KEY `payment_id` (`payment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypercut_webhook_log` (
                `log_id` int(11) NOT NULL AUTO_INCREMENT,
                `event_type` varchar(100) NOT NULL,
                `event_id` varchar(255) DEFAULT NULL,
                `payload` text NOT NULL,
                `processed` tinyint(1) NOT NULL DEFAULT 0,
                `error` text,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`log_id`),
                KEY `event_type` (`event_type`),
                KEY `event_id` (`event_id`),
                KEY `processed` (`processed`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        // Telemetry storage: the token, queue, inflight batch, runtime counters,
        // locks and sent log all live in this one table.
        Store::ensureSchema();

        // Register event for order info page to display Paypercut payment information
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent(
            'paypercut_order_info',
            'admin/view/sale/order_info/after',
            'extension/payment/paypercut_order/info'
        );

        // Every admin page shows a banner while a debug session is running.
        $this->model_setting_event->addEvent(
            'paypercut_telemetry_notice',
            'admin/view/common/header/after',
            'extension/payment/paypercut_telemetry/notice'
        );

        // Deploy the Apple Pay domain verification file to <webroot>/.well-known/.
        // Non-blocking: install must succeed even if the webroot is not writable.
        $applepay_deploy = $this->deployApplePayDomainAssociation();
        if (!$applepay_deploy['success']) {
            $existing_warning = isset($this->session->data['warning']) ? $this->session->data['warning'] . ' ' : '';
            $this->session->data['warning'] = $existing_warning . 'Paypercut installed, but Apple Pay verification file could not be deployed: ' . $applepay_deploy['message'] . ' Apple Pay will not work until this is resolved. Download the file from https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association and place it at ' . dirname(DIR_APPLICATION) . '/.well-known/apple-developer-merchantid-domain-association manually.';
        }
    }

    /**
     * Uninstall method - Called when extension is uninstalled
     * Removes events (but preserves database tables for data integrity)
     */
    public function uninstall()
    {
        // The single teardown path, so nothing is forgotten: this deletes the
        // token, the queue, the inflight batch and the runtime record.
        TelemetrySession::end('deactivated');

        // Then the rest of the telemetry storage: both locks and the sent log.
        Store::purge();

        // Remove events
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('paypercut_order_info');
        $this->model_setting_event->deleteEventByCode('paypercut_telemetry_notice');

        // Note: We intentionally don't drop database tables to preserve transaction history
        // If you want to completely remove all data, manually drop these tables:
        // - oc_paypercut_customer
        // - oc_paypercut_transaction
        // - oc_paypercut_refund
        // - oc_paypercut_webhook_log
    }
}
