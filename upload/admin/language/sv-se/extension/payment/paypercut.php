<?php
// Heading
$_['heading_title'] = 'Paypercut betalningsgateway';

// Text
$_['text_extension'] = 'Tillägg';
$_['text_success'] = 'Framgång: Du har ändrat Paypercut betalningsmodul!';
$_['text_edit'] = 'Redigera Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API-nyckel';
$_['entry_operating_account'] = 'Driftkonto-ID';
$_['entry_statement_descriptor'] = 'Kontoutdragsbeskrivning';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Betalningsläge';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Orderstatus';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sorteringsordning';
$_['entry_logging'] = 'Aktivera loggning';
$_['entry_payment_method_config'] = 'Betalningsmetodkonfiguration';
$_['entry_applepay_domain_file'] = 'Apple Pay-domänfil';

// Help
$_['help_api_key'] = 'Ange din Paypercut API-nyckel från instrumentpanelen';
$_['help_operating_account'] = 'Ange ditt driftkonto-ID (finns i Paypercut Dashboard)';
$_['help_statement_descriptor'] = 'Text som visas på kundens kontoutdrag (max 22 tecken). Lämna tomt för att använda standard.';
$_['help_google_pay'] = 'Aktivera Google Pay som betalningsalternativ';
$_['help_apple_pay'] = 'Aktivera Apple Pay som betalningsalternativ';
$_['help_checkout_mode'] = 'Välj mellan hostad (omdirigering till Paypercut-sida) eller inbäddad (betalning på din webbplats) betalningsupplevelse';
$_['help_webhook_url'] = 'Kopiera denna URL och konfigurera den i din Paypercut Dashboard under Utvecklare > Webhooks';
$_['help_logging'] = 'Aktivera loggning av API-förfrågningar, webhook-händelser och fel. Inaktivera i produktion om inte felsökning pågår. Loggar kan innehålla känsliga data.';
$_['help_payment_method_config'] = 'Välj en betalningsmetodkonfiguration (betalningsprofil) för att styra vilka betalningsmetoder som är tillgängliga för kunder. Lämna tomt för att använda standard.';
$_['help_applepay_domain_file'] = 'Apple Pay kräver en domänverifieringsfil på <code>/.well-known/apple-developer-merchantid-domain-association</code>. Tillägget distribuerar den automatiskt vid installation och när inställningar sparas.';

// Error
$_['error_permission'] = 'Varning: Du har inte behörighet att ändra Paypercut betalningsmodul!';
$_['error_api_key'] = 'API-nyckel krävs!';
$_['error_statement_descriptor'] = 'Kontoutdragsbeskrivning får vara högst 22 tecken!';
$_['error_unsupported_currency'] = 'Varning: Din butiks valuta (%s) stöds inte av Paypercut. Stödda valutor: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Testläge';
$_['text_mode_live'] = 'Live-läge';
$_['text_mode_unknown'] = 'Okänt läge';
$_['text_enabled'] = 'Aktiverad';
$_['text_disabled'] = 'Inaktiverad';
$_['text_hosted'] = 'Hostad (omdirigering)';
$_['text_embedded'] = 'Inbäddad (på webbplatsen)';
$_['text_statement_preview'] = 'Förhandsvisning';
$_['text_webhook_info'] = 'Konfigurera denna webhook URL i din <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut Dashboard</a>';
$_['text_webhook_configured'] = 'Webhook är konfigurerad och aktiv';
$_['text_webhook_not_configured'] = 'Webhook är inte konfigurerad';
$_['text_webhook_create'] = 'Skapa Webhook automatiskt';
$_['text_webhook_delete'] = 'Ta bort Webhook';
$_['text_webhook_creating'] = 'Skapar webhook...';
$_['text_webhook_deleting'] = 'Tar bort webhook...';
$_['text_wallet_settings'] = 'Plånboksinställningar';
$_['text_testing_connection'] = 'Testar anslutning...';
$_['text_connection_success'] = 'Anslutning lyckades!';
$_['text_connection_failed'] = 'Anslutning misslyckades';
$_['text_applepay_domain_ok'] = 'Apple Pay-domänfil verifierad';
$_['text_applepay_domain_warning'] = 'Apple Pay-domänfil distribuerad men inte verifierad';
$_['text_applepay_domain_missing'] = 'Apple Pay-domänfil saknas';
$_['text_applepay_domain_manual_help'] = 'Ladda ner <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">verifieringsfilen</a> och placera den manuellt på:';

// Button
$_['button_test_connection'] = 'Testa anslutning';
