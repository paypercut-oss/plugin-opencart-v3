<?php
// Heading
$_['heading_title'] = 'Bramka Płatności Paypercut';

// Text
$_['text_extension'] = 'Rozszerzenia';
$_['text_success'] = 'Sukces: Zmodyfikowano moduł płatności Paypercut!';
$_['text_edit'] = 'Edytuj Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'Klucz API';
$_['entry_operating_account'] = 'ID Konta Operacyjnego';
$_['entry_statement_descriptor'] = 'Deskryptor Wyciągu';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Tryb Realizacji Zamówienia';
$_['entry_webhook_url'] = 'Adres URL Webhooka';
$_['entry_order_status'] = 'Status Zamówienia';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Kolejność Sortowania';
$_['entry_logging'] = 'Włącz Logowanie';
$_['entry_payment_method_config'] = 'Konfiguracja Metod Płatności';

// Help
$_['help_api_key'] = 'Wprowadź swój klucz API Paypercut z panelu';
$_['help_operating_account'] = 'Wprowadź ID swojego konta operacyjnego (znajduje się w panelu Paypercut)';
$_['help_statement_descriptor'] = 'Tekst wyświetlany na wyciągu bankowym klienta (maks. 22 znaki). Pozostaw puste, aby użyć domyślnego.';
$_['help_google_pay'] = 'Włącz Google Pay jako opcję płatności';
$_['help_apple_pay'] = 'Włącz Apple Pay jako opcję płatności';
$_['help_checkout_mode'] = 'Wybierz między hostowanym (przekierowanie na stronę Paypercut) lub wbudowanym (płatność na Twojej stronie) doświadczeniem płatności';
$_['help_webhook_url'] = 'Skopiuj ten adres URL i skonfiguruj go w panelu Paypercut w zakładce Developers > Webhooks';
$_['help_logging'] = 'Włącz logowanie żądań API, zdarzeń webhook i błędów. Wyłącz w środowisku produkcyjnym, chyba że debugujesz. Logi mogą zawierać poufne dane.';
$_['help_payment_method_config'] = 'Wybierz konfigurację metody płatności (profil płatności), aby kontrolować, które metody płatności są dostępne dla klientów. Pozostaw puste, aby użyć domyślnej.';

// Error
$_['error_permission'] = 'Ostrzeżenie: Nie masz uprawnień do modyfikacji modułu płatności Paypercut!';
$_['error_api_key'] = 'Wymagany klucz API!';
$_['error_statement_descriptor'] = 'Deskryptor wyciągu nie może przekraczać 22 znaków!';
$_['error_unsupported_currency'] = 'Ostrzeżenie: Waluta Twojego sklepu (%s) nie jest obsługiwana przez Paypercut. Obsługiwane waluty: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Tryb Testowy';
$_['text_mode_live'] = 'Tryb Produkcyjny';
$_['text_mode_unknown'] = 'Nieznany Tryb';
$_['text_enabled'] = 'Włączony';
$_['text_disabled'] = 'Wyłączony';
$_['text_hosted'] = 'Hostowany (Przekierowanie)';
$_['text_embedded'] = 'Wbudowany (Na stronie)';
$_['text_statement_preview'] = 'Podgląd';
$_['text_webhook_info'] = 'Skonfiguruj ten adres URL webhooka w swoim <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">panelu Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook jest skonfigurowany i aktywny';
$_['text_webhook_not_configured'] = 'Webhook nie jest skonfigurowany';
$_['text_webhook_create'] = 'Utwórz Webhook Automatycznie';
$_['text_webhook_delete'] = 'Usuń Webhook';
$_['text_webhook_creating'] = 'Tworzenie webhooka...';
$_['text_webhook_deleting'] = 'Usuwanie webhooka...';
$_['text_wallet_settings'] = 'Ustawienia Portfela';
$_['text_testing_connection'] = 'Testowanie połączenia...';
$_['text_connection_success'] = 'Połączenie udane!';
$_['text_connection_failed'] = 'Połączenie nieudane';

// Button
$_['button_test_connection'] = 'Testuj Połączenie';
