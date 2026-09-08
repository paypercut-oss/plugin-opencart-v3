<?php
// Heading
$_['heading_title'] = 'Paypercut Debug Session';

// Panel
$_['text_telemetry_title'] = 'Debug session';
$_['text_telemetry_idle_lead'] = 'Off. Nothing is sent to Paypercut until you start a session.';
$_['text_telemetry_idle_help'] = 'Turn on detailed diagnostics for about an hour so Paypercut support can see what your store is doing. The session ends by itself.';
$_['text_telemetry_running'] = 'Debug session running — %s remaining';
$_['text_telemetry_started_by'] = 'Started by %s · ends at %s';
$_['text_telemetry_session_id'] = 'Session ID';
$_['text_telemetry_last_session_id'] = 'Last session ID';
$_['text_telemetry_quote_it'] = 'Quote this in your support ticket.';
$_['text_telemetry_counters'] = '%s events sent · %s dropped (approximate)';
$_['text_telemetry_ended_lead'] = 'Debug session ended.';
$_['text_telemetry_ended_help'] = 'Paypercut stops receiving data from this store.';
$_['text_telemetry_support_reference'] = 'Support reference';
$_['text_telemetry_stopping'] = 'Stopping...';
$_['text_telemetry_starting'] = 'Starting...';
$_['text_telemetry_copied'] = 'Copied';
$_['text_telemetry_session_ended_notice'] = 'Debug session ended. Paypercut stops receiving data from this store.';
$_['text_telemetry_admin_unreachable'] = 'This store stopped answering the debug session requests. Reload the page to try again.';
$_['text_telemetry_network_error'] = 'The request could not be completed. Check your connection and try again.';
$_['text_telemetry_notice'] = 'Paypercut: a debug session started by %s is running until %s.';
$_['text_telemetry_manage'] = 'Manage it';

// Consent
$_['text_telemetry_shared_summary'] = 'What is shared';
$_['text_telemetry_shared'] = 'Module, OpenCart, PHP and theme versions; the extensions installed on this store and their versions; how this store has the Paypercut module configured (which checkout mode is selected and which options are switched on — never the values of your credentials); a record of each checkout, refund and payment notification the module handled and whether it succeeded, identified by OpenCart order id and Paypercut payment reference; when something fails, the kind of error, a short description this module wrote for it, the file and line it came from, and which extension or theme raised it; and when the session started and stopped.';
$_['text_telemetry_not_shared_label'] = 'Not shared:';
$_['text_telemetry_not_shared'] = 'customer names, email addresses, billing or shipping addresses, order totals, line items, payment card data, the reason text you type when issuing a refund, the wording of errors raised by OpenCart or by another extension, or any API key, webhook secret or password.';
$_['text_telemetry_key_use'] = 'Your API key is never sent to the telemetry service. It is used once, over HTTPS, to obtain a short-lived diagnostic token from the Paypercut API for the environment this store is connected to.';
$_['text_telemetry_retention'] = 'Paypercut keeps this diagnostic data for 30 days.';
$_['text_telemetry_modal_title'] = 'Start a debug session?';
$_['text_telemetry_modal_lead'] = 'While the session is running, this store sends the diagnostic information below to Paypercut so support can see what is happening.';
$_['text_telemetry_modal_help'] = 'The session lasts about 60 minutes and then stops by itself. You can stop it sooner at any time.';

// Sent log
$_['text_telemetry_log_summary'] = 'Show the %s events sent';
$_['text_telemetry_log_help'] = 'Exactly what was sent to Paypercut, newest last. The most recent %s are kept on this store and cleared when a new session starts.';
$_['text_telemetry_log_time'] = 'Time (UTC)';
$_['text_telemetry_log_event'] = 'Event';
$_['text_telemetry_log_detail'] = 'Detail';
$_['text_telemetry_log_raw'] = 'Show raw JSON';

// Button
$_['button_telemetry_start'] = 'Start debug session';
$_['button_telemetry_retry'] = 'Try again';
$_['button_telemetry_stop'] = 'Stop now';
$_['button_telemetry_confirm'] = 'Start session';
$_['button_telemetry_cancel'] = 'Cancel';
$_['button_telemetry_copy'] = 'Copy';
$_['button_telemetry_copy_json'] = 'Copy JSON';

// Error
$_['error_telemetry_permission'] = 'Insufficient permissions.';
$_['error_telemetry_no_api_key'] = 'Enter and save your Paypercut API key before starting a debug session.';
$_['error_telemetry_no_environment'] = 'This store has not recorded which Paypercut environment it uses, so a debug session cannot be started. Save the Paypercut settings once, then try again.';
$_['error_telemetry_unsupported_environment'] = "Debug sessions aren't available on this store's Paypercut environment.";
$_['error_telemetry_starting'] = 'A debug session is already being started.';
$_['error_telemetry_network'] = "Your server couldn't reach Paypercut to start the debug session. Check that outbound HTTPS requests are allowed by your host or firewall, then try again.";
$_['error_telemetry_key_invalid'] = "Paypercut couldn't verify this store's API key, so the debug session was not started and nothing was sent. Use Test Connection above, or re-enter your API key, then try again.";
$_['error_telemetry_key_ineligible'] = "This store's Paypercut API key isn't eligible for debug sessions yet — this usually means the key isn't fully activated on your Paypercut account. Nothing has been sent. Contact Paypercut support and quote your account name from the API Configuration tab above.";
$_['error_telemetry_request_rejected'] = 'Paypercut rejected the debug session request. Nothing has been sent. Contact Paypercut support if this keeps happening.';
$_['error_telemetry_account_refused'] = "This store's Paypercut account isn't allowed to start debug sessions. Contact Paypercut support.";
$_['error_telemetry_not_available'] = "Debug sessions aren't available for this store's Paypercut environment yet. Nothing was sent.";
$_['error_telemetry_rate_limited'] = 'Too many attempts. Wait about a minute and try again.';
$_['error_telemetry_unavailable'] = "Paypercut's debug service is temporarily unavailable. Please try again in a few minutes.";
$_['error_telemetry_service'] = "Paypercut couldn't issue a debug token. Please try again — if it keeps happening, contact support and quote the reference below.";
$_['error_telemetry_unexpected'] = 'Paypercut returned an unexpected response. The debug session was not started — please try again.';
$_['error_telemetry_clock_skew'] = "This server's clock appears to be out of sync with Paypercut (off by about %d minutes), so a debug session can't be started. Ask your host to enable time synchronisation (NTP), then try again.";
