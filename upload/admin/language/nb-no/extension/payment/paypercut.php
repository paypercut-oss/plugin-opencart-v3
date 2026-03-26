<?php
// Heading
$_['heading_title'] = 'Paypercut Betalingsgateway';

// Text
$_['text_extension'] = 'Utvidelser';
$_['text_success'] = 'Suksess: Du har endret Paypercut betalingsmodul!';
$_['text_edit'] = 'Rediger Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API-nøkkel';
$_['entry_operating_account'] = 'Driftskonto-ID';
$_['entry_statement_descriptor'] = 'Kontoutskriftsbeskrivelse';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Kassemodus';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Ordrestatus';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sorteringsrekkefølge';
$_['entry_logging'] = 'Aktiver logging';
$_['entry_payment_method_config'] = 'Betalingsmetode-konfigurasjon';

// Help
$_['help_api_key'] = 'Skriv inn din Paypercut API-nøkkel fra kontrollpanelet';
$_['help_operating_account'] = 'Skriv inn din Driftskonto-ID (finnes i Paypercut-kontrollpanelet)';
$_['help_statement_descriptor'] = 'Tekst som vises på kundens kontoutskrift (maks 22 tegn). La stå tom for å bruke standard.';
$_['help_google_pay'] = 'Aktiver Google Pay som betalingsalternativ';
$_['help_apple_pay'] = 'Aktiver Apple Pay som betalingsalternativ';
$_['help_checkout_mode'] = 'Velg mellom vertshåndtert (omdirigering til Paypercut-side) eller innebygd (kasse på din side) betalingsopplevelse';
$_['help_webhook_url'] = 'Kopier denne URL-en og konfigurer den i Paypercut-kontrollpanelet under Utviklere > Webhooks';
$_['help_logging'] = 'Aktiver logging av API-forespørsler, webhook-hendelser og feil. Deaktiver i produksjon med mindre du feilsøker. Logger kan inneholde sensitive data.';
$_['help_payment_method_config'] = 'Velg en betalingsmetode-konfigurasjon (betalingsprofil) for å kontrollere hvilke betalingsmetoder som er tilgjengelige for kunder. La stå tom for å bruke standard.';

// Error
$_['error_permission'] = 'Advarsel: Du har ikke tillatelse til å endre Paypercut betalingsmodul!';
$_['error_api_key'] = 'API-nøkkel er påkrevd!';
$_['error_statement_descriptor'] = 'Kontoutskriftsbeskrivelsen må være 22 tegn eller mindre!';
$_['error_unsupported_currency'] = 'Advarsel: Din butikkvaluta (%s) støttes ikke av Paypercut. Støttede valutaer: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Testmodus';
$_['text_mode_live'] = 'Produksjonsmodus';
$_['text_mode_unknown'] = 'Ukjent modus';
$_['text_enabled'] = 'Aktivert';
$_['text_disabled'] = 'Deaktivert';
$_['text_hosted'] = 'Vertshåndtert (Omdirigering)';
$_['text_embedded'] = 'Innebygd (På siden)';
$_['text_statement_preview'] = 'Forhåndsvisning';
$_['text_webhook_info'] = 'Konfigurer denne webhook URL-en i ditt <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut-kontrollpanel</a>';
$_['text_webhook_configured'] = 'Webhook er konfigurert og aktiv';
$_['text_webhook_not_configured'] = 'Webhook ikke konfigurert';
$_['text_webhook_create'] = 'Opprett webhook automatisk';
$_['text_webhook_delete'] = 'Slett webhook';
$_['text_webhook_creating'] = 'Oppretter webhook...';
$_['text_webhook_deleting'] = 'Sletter webhook...';
$_['text_wallet_settings'] = 'Lommebokinnstillinger';
$_['text_testing_connection'] = 'Tester tilkobling...';
$_['text_connection_success'] = 'Tilkobling vellykket!';
$_['text_connection_failed'] = 'Tilkobling mislyktes';

// Button
$_['button_test_connection'] = 'Test tilkobling';
