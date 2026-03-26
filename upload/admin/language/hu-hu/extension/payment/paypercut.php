<?php
// Heading
$_['heading_title'] = 'Paypercut Fizetési Átjáró';

// Text
$_['text_extension'] = 'Bővítmények';
$_['text_success'] = 'Siker: Módosította a Paypercut fizetési modult!';
$_['text_edit'] = 'Paypercut szerkesztése';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API kulcs';
$_['entry_operating_account'] = 'Működési számla azonosító';
$_['entry_statement_descriptor'] = 'Számla leírás';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Fizetési mód';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Rendelés állapota';
$_['entry_status'] = 'Állapot';
$_['entry_sort_order'] = 'Rendezési sorrend';
$_['entry_logging'] = 'Naplózás engedélyezése';
$_['entry_payment_method_config'] = 'Fizetési módszer konfiguráció';

// Help
$_['help_api_key'] = 'Adja meg a Paypercut API kulcsát a vezérlőpultból';
$_['help_operating_account'] = 'Adja meg a működési számla azonosítóját (megtalálható a Paypercut vezérlőpultban)';
$_['help_statement_descriptor'] = 'Szöveg, amely megjelenik az ügyfél bankszámla kivonatán (maximum 22 karakter). Hagyja üresen az alapértelmezett használatához.';
$_['help_google_pay'] = 'Google Pay engedélyezése fizetési lehetőségként';
$_['help_apple_pay'] = 'Apple Pay engedélyezése fizetési lehetőségként';
$_['help_checkout_mode'] = 'Válasszon a tárolt (átirányítás a Paypercut oldalára) vagy beágyazott (fizetés a saját webhelyén) fizetési élmény között';
$_['help_webhook_url'] = 'Másolja ezt az URL-t és konfigurálja a Paypercut vezérlőpultban a Fejlesztők > Webhookok menüpontban';
$_['help_logging'] = 'API kérések, webhook események és hibák naplózásának engedélyezése. Tiltsa le éles környezetben, kivéve hibakereséskor. A naplók érzékeny adatokat tartalmazhatnak.';
$_['help_payment_method_config'] = 'Válasszon ki egy fizetési módszer konfigurációt (fizetési profil) annak szabályozására, hogy mely fizetési módok érhetők el az ügyfelek számára. Hagyja üresen az alapértelmezett használatához.';

// Error
$_['error_permission'] = 'Figyelmeztetés: Nincs jogosultsága a Paypercut fizetési modul módosítására!';
$_['error_api_key'] = 'API kulcs kötelező!';
$_['error_statement_descriptor'] = 'A számla leírás nem lehet hosszabb 22 karakternél!';
$_['error_unsupported_currency'] = 'Figyelmeztetés: Az áruház pénzneme (%s) nem támogatott a Paypercut által. Támogatott pénznemek: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Teszt mód';
$_['text_mode_live'] = 'Éles mód';
$_['text_mode_unknown'] = 'Ismeretlen mód';
$_['text_enabled'] = 'Engedélyezve';
$_['text_disabled'] = 'Letiltva';
$_['text_hosted'] = 'Tárolt (átirányítás)';
$_['text_embedded'] = 'Beágyazott (helyszíni)';
$_['text_statement_preview'] = 'Előnézet';
$_['text_webhook_info'] = 'Konfigurálja ezt a webhook URL-t a <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut vezérlőpultban</a>';
$_['text_webhook_configured'] = 'Webhook be van állítva és aktív';
$_['text_webhook_not_configured'] = 'Webhook nincs beállítva';
$_['text_webhook_create'] = 'Webhook automatikus létrehozása';
$_['text_webhook_delete'] = 'Webhook törlése';
$_['text_webhook_creating'] = 'Webhook létrehozása...';
$_['text_webhook_deleting'] = 'Webhook törlése...';
$_['text_wallet_settings'] = 'Tárca beállítások';
$_['text_testing_connection'] = 'Kapcsolat tesztelése...';
$_['text_connection_success'] = 'Kapcsolat sikeres!';
$_['text_connection_failed'] = 'Kapcsolat sikertelen';

// Button
$_['button_test_connection'] = 'Kapcsolat tesztelése';
