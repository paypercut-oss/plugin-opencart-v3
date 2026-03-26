<?php
// Heading
$_['heading_title'] = 'Paypercut betalingsgateway';

// Text
$_['text_extension'] = 'Udvidelser';
$_['text_success'] = 'Succes: Du har ændret Paypercut betalingsmodulet!';
$_['text_edit'] = 'Rediger Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API-nøgle';
$_['entry_operating_account'] = 'Driftskonto-ID';
$_['entry_statement_descriptor'] = 'Kontoudtogsbeskrivelse';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Betalingstilstand';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Ordrestatus';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sorteringsrækkefølge';
$_['entry_logging'] = 'Aktivér logning';
$_['entry_payment_method_config'] = 'Betalingsmetodekonfiguration';

// Help
$_['help_api_key'] = 'Indtast din Paypercut API-nøgle fra dashboardet';
$_['help_operating_account'] = 'Indtast dit driftskonto-ID (findes i Paypercut Dashboard)';
$_['help_statement_descriptor'] = 'Tekst der vises på kundens kontoudtog (maks. 22 tegn). Lad stå tomt for at bruge standard.';
$_['help_google_pay'] = 'Aktivér Google Pay som betalingsmulighed';
$_['help_apple_pay'] = 'Aktivér Apple Pay som betalingsmulighed';
$_['help_checkout_mode'] = 'Vælg mellem hostet (omdirigering til Paypercut-side) eller indlejret (betaling på dit websted) betalingsoplevelse';
$_['help_webhook_url'] = 'Kopiér denne URL og konfigurér den i dit Paypercut Dashboard under Udviklere > Webhooks';
$_['help_logging'] = 'Aktivér logning af API-anmodninger, webhook-hændelser og fejl. Deaktiver i produktion, medmindre der fejlsøges. Logs kan indeholde følsomme data.';
$_['help_payment_method_config'] = 'Vælg en betalingsmetodekonfiguration (betalingsprofil) for at styre, hvilke betalingsmetoder der er tilgængelige for kunder. Lad stå tomt for at bruge standard.';

// Error
$_['error_permission'] = 'Advarsel: Du har ikke tilladelse til at ændre Paypercut betalingsmodulet!';
$_['error_api_key'] = 'API-nøgle er påkrævet!';
$_['error_statement_descriptor'] = 'Kontoudtogsbeskrivelse må højst være 22 tegn!';
$_['error_unsupported_currency'] = 'Advarsel: Din butiks valuta (%s) understøttes ikke af Paypercut. Understøttede valutaer: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Testtilstand';
$_['text_mode_live'] = 'Live-tilstand';
$_['text_mode_unknown'] = 'Ukendt tilstand';
$_['text_enabled'] = 'Aktiveret';
$_['text_disabled'] = 'Deaktiveret';
$_['text_hosted'] = 'Hostet (omdirigering)';
$_['text_embedded'] = 'Indlejret (på webstedet)';
$_['text_statement_preview'] = 'Forhåndsvisning';
$_['text_webhook_info'] = 'Konfigurér denne webhook URL i dit <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut Dashboard</a>';
$_['text_webhook_configured'] = 'Webhook er konfigureret og aktiv';
$_['text_webhook_not_configured'] = 'Webhook er ikke konfigureret';
$_['text_webhook_create'] = 'Opret Webhook automatisk';
$_['text_webhook_delete'] = 'Slet Webhook';
$_['text_webhook_creating'] = 'Opretter webhook...';
$_['text_webhook_deleting'] = 'Sletter webhook...';
$_['text_wallet_settings'] = 'Wallet-indstillinger';
$_['text_testing_connection'] = 'Tester forbindelse...';
$_['text_connection_success'] = 'Forbindelse oprettet!';
$_['text_connection_failed'] = 'Forbindelse mislykkedes';

// Button
$_['button_test_connection'] = 'Test forbindelse';
