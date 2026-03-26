<?php
// Heading
$_['heading_title'] = 'Paypercut Payments';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified Paypercut payment module!';
$_['text_edit'] = 'Edit Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API Key';
$_['entry_operating_account'] = 'Operating Account ID';
$_['entry_statement_descriptor'] = 'Statement Descriptor';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Checkout Mode';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Order Status';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_logging'] = 'Enable Logging';
$_['entry_payment_method_config'] = 'Payment Method Configuration';

// Help
$_['help_api_key'] = 'Enter your Paypercut API Key from the dashboard';
$_['help_operating_account'] = 'Enter your Operating Account ID (found in Paypercut Dashboard)';
$_['help_statement_descriptor'] = 'Text that appears on customer\'s bank statement (max 22 characters). Leave empty to use default.';
$_['help_google_pay'] = 'Enable Google Pay as a payment option';
$_['help_apple_pay'] = 'Enable Apple Pay as a payment option';
$_['help_checkout_mode'] = 'Choose between hosted (redirect to Paypercut page) or embedded (checkout on your site) payment experience';
$_['help_webhook_url'] = 'Copy this URL and configure it in your Paypercut Dashboard under Developers > Webhooks';
$_['help_logging'] = 'Enable logging of API requests, webhook events, and errors. Disable in production unless debugging. Logs may contain sensitive data.';
$_['help_payment_method_config'] = 'Select a payment method configuration (payment profile) to control which payment methods are available to customers. Leave empty to use default.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Paypercut payment module!';
$_['error_api_key'] = 'API Key Required!';
$_['error_statement_descriptor'] = 'Statement descriptor must be 22 characters or less!';
$_['error_unsupported_currency'] = 'Warning: Your store currency (%s) is not supported by Paypercut. Supported currencies: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Test Mode';
$_['text_mode_live'] = 'Live Mode';
$_['text_mode_unknown'] = 'Unknown Mode';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_hosted'] = 'Hosted (Redirect)';
$_['text_embedded'] = 'Embedded (On-site)';
$_['text_statement_preview'] = 'Preview';
$_['text_webhook_info'] = 'Configure this webhook URL in your <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut Dashboard</a>';
$_['text_webhook_configured'] = 'Webhook is configured and active';
$_['text_webhook_not_configured'] = 'Webhook not configured';
$_['text_webhook_create'] = 'Create Webhook Automatically';
$_['text_webhook_delete'] = 'Delete Webhook';
$_['text_webhook_creating'] = 'Creating webhook...';
$_['text_webhook_deleting'] = 'Deleting webhook...';
$_['text_wallet_settings'] = 'Wallet Settings';
$_['text_testing_connection'] = 'Testing connection...';
$_['text_connection_success'] = 'Connection successful!';
$_['text_connection_failed'] = 'Connection failed';

// Refund
$_['text_refund_success'] = 'Refund processed successfully!';
$_['error_order_id'] = 'Order ID is required!';
$_['error_no_transaction'] = 'No Paypercut transaction found for this order.';
$_['error_payment_not_succeeded'] = 'Only succeeded payments can be refunded.';
$_['error_already_refunded'] = 'This payment has already been fully refunded.';
$_['error_invalid_amount'] = 'Please enter a valid refund amount.';
$_['error_exceeds_payment'] = 'Refund amount exceeds the remaining payment amount.';
$_['error_api_key_missing'] = 'Paypercut API key is not configured.';
$_['error_connection'] = 'Could not connect to Paypercut API. Please try again.';
$_['error_timeout'] = 'Connection to Paypercut API timed out. Please try again.';
$_['error_refund_failed'] = 'Refund failed. Please try again or contact support.';

// Button
$_['button_test_connection'] = 'Test Connection';
