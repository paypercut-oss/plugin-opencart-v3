<?php
// Heading
$_['heading_title'] = 'Gateway de Plată Paypercut';

// Text
$_['text_extension'] = 'Extensii';
$_['text_success'] = 'Succes: Ați modificat modulul de plată Paypercut!';
$_['text_edit'] = 'Editează Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'Cheie API';
$_['entry_operating_account'] = 'ID Cont Operațional';
$_['entry_statement_descriptor'] = 'Descriptor Extras';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Mod Finalizare Comandă';
$_['entry_webhook_url'] = 'URL Webhook';
$_['entry_order_status'] = 'Status Comandă';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Ordine Sortare';
$_['entry_logging'] = 'Activează Jurnalizarea';
$_['entry_payment_method_config'] = 'Configurare Metodă de Plată';

// Help
$_['help_api_key'] = 'Introduceți cheia API Paypercut din tabloul de bord';
$_['help_operating_account'] = 'Introduceți ID-ul contului operațional (se găsește în tabloul de bord Paypercut)';
$_['help_statement_descriptor'] = 'Text care apare pe extrasul bancar al clientului (maxim 22 caractere). Lăsați gol pentru a utiliza valoarea implicită.';
$_['help_google_pay'] = 'Activează Google Pay ca opțiune de plată';
$_['help_apple_pay'] = 'Activează Apple Pay ca opțiune de plată';
$_['help_checkout_mode'] = 'Alegeți între găzduit (redirecționare către pagina Paypercut) sau încorporat (finalizare pe site-ul dvs.) experiență de plată';
$_['help_webhook_url'] = 'Copiați acest URL și configurați-l în tabloul de bord Paypercut la Dezvoltatori > Webhooks';
$_['help_logging'] = 'Activează jurnalizarea cererilor API, evenimentelor webhook și erorilor. Dezactivați în producție, cu excepția cazului în care depanați. Jurnalele pot conține date sensibile.';
$_['help_payment_method_config'] = 'Selectați o configurare a metodei de plată (profil de plată) pentru a controla care metode de plată sunt disponibile pentru clienți. Lăsați gol pentru a utiliza valoarea implicită.';

// Error
$_['error_permission'] = 'Avertisment: Nu aveți permisiunea de a modifica modulul de plată Paypercut!';
$_['error_api_key'] = 'Cheie API necesară!';
$_['error_statement_descriptor'] = 'Descriptorul extrasului trebuie să aibă maxim 22 de caractere!';
$_['error_unsupported_currency'] = 'Avertisment: Moneda magazinului dvs. (%s) nu este suportată de Paypercut. Monede suportate: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Mod Test';
$_['text_mode_live'] = 'Mod Live';
$_['text_mode_unknown'] = 'Mod Necunoscut';
$_['text_enabled'] = 'Activat';
$_['text_disabled'] = 'Dezactivat';
$_['text_hosted'] = 'Găzduit (Redirecționare)';
$_['text_embedded'] = 'Încorporat (Pe site)';
$_['text_statement_preview'] = 'Previzualizare';
$_['text_webhook_info'] = 'Configurați acest URL webhook în <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">tabloul de bord Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook-ul este configurat și activ';
$_['text_webhook_not_configured'] = 'Webhook neconfigurat';
$_['text_webhook_create'] = 'Creează Webhook Automat';
$_['text_webhook_delete'] = 'Șterge Webhook';
$_['text_webhook_creating'] = 'Se creează webhook...';
$_['text_webhook_deleting'] = 'Se șterge webhook...';
$_['text_wallet_settings'] = 'Setări Portofel';
$_['text_testing_connection'] = 'Se testează conexiunea...';
$_['text_connection_success'] = 'Conexiune reușită!';
$_['text_connection_failed'] = 'Conexiune eșuată';

// Button
$_['button_test_connection'] = 'Testează Conexiunea';
