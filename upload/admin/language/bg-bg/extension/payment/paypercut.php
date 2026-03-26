<?php
// Heading
$_['heading_title'] = 'Paypercut платежен метод';

// Text
$_['text_extension'] = 'Разширения';
$_['text_success'] = 'Успех: Успешно променихте модула за плащане Paypercut!';
$_['text_edit'] = 'Редактиране на Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API ключ';
$_['entry_operating_account'] = 'ID на оперативна сметка';
$_['entry_statement_descriptor'] = 'Дескриптор на извлечение';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Режим на плащане';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_order_status'] = 'Статус на поръчката';
$_['entry_status'] = 'Статус';
$_['entry_sort_order'] = 'Ред на сортиране';
$_['entry_logging'] = 'Активиране на логване';
$_['entry_payment_method_config'] = 'Конфигурация на метод за плащане';

// Help
$_['help_api_key'] = 'Въведете вашия Paypercut API ключ от таблото за управление';
$_['help_operating_account'] = 'Въведете вашия ID на оперативна сметка (намира се в таблото за управление на Paypercut)';
$_['help_statement_descriptor'] = 'Текст, който се появява в банковото извлечение на клиента (макс. 22 символа). Оставете празно, за да използвате по подразбиране.';
$_['help_google_pay'] = 'Активирайте Google Pay като опция за плащане';
$_['help_apple_pay'] = 'Активирайте Apple Pay като опция за плащане';
$_['help_checkout_mode'] = 'Изберете между хоствано (пренасочване към страница на Paypercut) или вградено (плащане на вашия сайт) изживяване при плащане';
$_['help_webhook_url'] = 'Копирайте този URL и го конфигурирайте в таблото за управление на Paypercut в секция Разработчици > Webhooks';
$_['help_logging'] = 'Активирайте логване на API заявки, webhook събития и грешки. Деактивирайте в продукция, освен ако не отстранявате грешки. Логовете могат да съдържат чувствителни данни.';
$_['help_payment_method_config'] = 'Изберете конфигурация на метод за плащане (платежен профил), за да контролирате кои методи за плащане са достъпни за клиентите. Оставете празно, за да използвате по подразбиране.';

// Error
$_['error_permission'] = 'Предупреждение: Нямате разрешение да променяте модула за плащане Paypercut!';
$_['error_api_key'] = 'Изисква се API ключ!';
$_['error_statement_descriptor'] = 'Дескрипторът на извлечение трябва да бъде максимум 22 символа!';
$_['error_unsupported_currency'] = 'Предупреждение: Валутата на вашия магазин (%s) не се поддържа от Paypercut. Поддържани валути: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Тестов режим';
$_['text_mode_live'] = 'Реален режим';
$_['text_mode_unknown'] = 'Неизвестен режим';
$_['text_enabled'] = 'Активиран';
$_['text_disabled'] = 'Деактивиран';
$_['text_hosted'] = 'Хоствано (Пренасочване)';
$_['text_embedded'] = 'Вградено (На сайта)';
$_['text_statement_preview'] = 'Преглед';
$_['text_webhook_info'] = 'Конфигурирайте този webhook URL в таблото за управление на <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook е конфигуриран и активен';
$_['text_webhook_not_configured'] = 'Webhook не е конфигуриран';
$_['text_webhook_create'] = 'Създаване на Webhook автоматично';
$_['text_webhook_delete'] = 'Изтриване на Webhook';
$_['text_webhook_creating'] = 'Създаване на webhook...';
$_['text_webhook_deleting'] = 'Изтриване на webhook...';
$_['text_wallet_settings'] = 'Настройки на портфейл';
$_['text_testing_connection'] = 'Тестване на връзката...';
$_['text_connection_success'] = 'Връзката е успешна!';
$_['text_connection_failed'] = 'Връзката се провали';

// Button
$_['button_test_connection'] = 'Тест на връзката';
