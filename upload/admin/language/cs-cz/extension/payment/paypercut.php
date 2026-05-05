<?php
// Heading
$_['heading_title'] = 'Paypercut Payments';

// Text
$_['text_extension'] = 'Rozšíření';
$_['text_success'] = 'Úspěch: Upravili jste platební modul Paypercut!';
$_['text_edit'] = 'Upravit Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API klíč';
$_['entry_operating_account'] = 'ID provozního účtu';
$_['entry_statement_descriptor'] = 'Popisek na výpisu';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Režim platby';
$_['entry_webhook_url'] = 'URL webhooku';
$_['entry_order_status'] = 'Stav objednávky';
$_['entry_status'] = 'Stav';
$_['entry_sort_order'] = 'Pořadí řazení';
$_['entry_logging'] = 'Povolit logování';
$_['entry_payment_method_config'] = 'Konfigurace platební metody';
$_['entry_applepay_domain_file'] = 'Soubor domény Apple Pay';

// Help
$_['help_api_key'] = 'Zadejte váš API klíč Paypercut z dashboardu';
$_['help_operating_account'] = 'Zadejte ID vašeho provozního účtu (najdete v Paypercut Dashboardu)';
$_['help_statement_descriptor'] = 'Text, který se zobrazí na bankovním výpisu zákazníka (max 22 znaků). Nechte prázdné pro použití výchozího.';
$_['help_google_pay'] = 'Povolit Google Pay jako platební možnost';
$_['help_apple_pay'] = 'Povolit Apple Pay jako platební možnost';
$_['help_checkout_mode'] = 'Vyberte mezi hostovaným (přesměrování na stránku Paypercut) nebo vloženým (platba na vašem webu) platebním prostředím';
$_['help_webhook_url'] = 'Zkopírujte tuto URL a nakonfigurujte ji ve vašem Paypercut Dashboardu v sekci Developers > Webhooks';
$_['help_logging'] = 'Povolit logování API požadavků, webhook událostí a chyb. Vypněte v produkci, pokud neladíte. Logy mohou obsahovat citlivá data.';
$_['help_payment_method_config'] = 'Vyberte konfiguraci platební metody (platební profil) pro kontrolu, které platební metody jsou dostupné zákazníkům. Nechte prázdné pro použití výchozího.';
$_['help_applepay_domain_file'] = 'Apple Pay vyžaduje ověřovací soubor na adrese <code>/.well-known/apple-developer-merchantid-domain-association</code>. Plugin jej automaticky nasadí při instalaci a při uložení nastavení.';

// Error
$_['error_permission'] = 'Varování: Nemáte oprávnění upravovat platební modul Paypercut!';
$_['error_api_key'] = 'API klíč je povinný!';
$_['error_statement_descriptor'] = 'Popisek na výpisu nesmí být delší než 22 znaků!';
$_['error_unsupported_currency'] = 'Varování: Měna vašeho obchodu (%s) není podporována Paypercut. Podporované měny: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Testovací režim';
$_['text_mode_live'] = 'Produkční režim';
$_['text_mode_unknown'] = 'Neznámý režim';
$_['text_enabled'] = 'Povoleno';
$_['text_disabled'] = 'Zakázáno';
$_['text_hosted'] = 'Hostovaný (přesměrování)';
$_['text_embedded'] = 'Vložený (na webu)';
$_['text_statement_preview'] = 'Náhled';
$_['text_webhook_info'] = 'Nakonfigurujte tuto URL webhooku ve vašem <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut Dashboardu</a>';
$_['text_webhook_configured'] = 'Webhook je nakonfigurován a aktivní';
$_['text_webhook_not_configured'] = 'Webhook není nakonfigurován';
$_['text_webhook_create'] = 'Vytvořit webhook automaticky';
$_['text_webhook_delete'] = 'Smazat webhook';
$_['text_webhook_creating'] = 'Vytváření webhooku...';
$_['text_webhook_deleting'] = 'Mazání webhooku...';
$_['text_wallet_settings'] = 'Nastavení peněženky';
$_['text_testing_connection'] = 'Testování připojení...';
$_['text_connection_success'] = 'Připojení úspěšné!';
$_['text_connection_failed'] = 'Připojení selhalo';
$_['text_applepay_domain_ok'] = 'Soubor domény Apple Pay byl ověřen';
$_['text_applepay_domain_warning'] = 'Soubor domény Apple Pay byl nasazen, ale není ověřen';
$_['text_applepay_domain_missing'] = 'Soubor domény Apple Pay chybí';
$_['text_applepay_domain_manual_help'] = 'Stáhněte <a href="https://cdn.paypercut.io/.well-known/apple-developer-merchantid-domain-association" target="_blank">ověřovací soubor</a> a ručně jej umístěte do:';

// Button
$_['button_test_connection'] = 'Test připojení';
