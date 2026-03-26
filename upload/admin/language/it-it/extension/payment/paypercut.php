<?php
// Heading
$_['heading_title'] = 'Gateway di Pagamento Paypercut';

// Text
$_['text_extension'] = 'Estensioni';
$_['text_success'] = 'Operazione riuscita: Hai modificato il modulo di pagamento Paypercut!';
$_['text_edit'] = 'Modifica Paypercut';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'Chiave API';
$_['entry_operating_account'] = 'ID Conto Operativo';
$_['entry_statement_descriptor'] = 'Descrizione Estratto Conto';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Modalità Checkout';
$_['entry_webhook_url'] = 'URL Webhook';
$_['entry_order_status'] = 'Stato Ordine';
$_['entry_status'] = 'Stato';
$_['entry_sort_order'] = 'Ordine di Visualizzazione';
$_['entry_logging'] = 'Abilita Registrazione';
$_['entry_payment_method_config'] = 'Configurazione Metodo di Pagamento';

// Help
$_['help_api_key'] = 'Inserisci la tua Chiave API Paypercut dalla dashboard';
$_['help_operating_account'] = 'Inserisci il tuo ID Conto Operativo (disponibile nella Dashboard Paypercut)';
$_['help_statement_descriptor'] = 'Testo che appare sull\'estratto conto bancario del cliente (max 22 caratteri). Lasciare vuoto per utilizzare il predefinito.';
$_['help_google_pay'] = 'Abilita Google Pay come opzione di pagamento';
$_['help_apple_pay'] = 'Abilita Apple Pay come opzione di pagamento';
$_['help_checkout_mode'] = 'Scegli tra hosted (reindirizzamento alla pagina Paypercut) o embedded (checkout sul tuo sito) per l\'esperienza di pagamento';
$_['help_webhook_url'] = 'Copia questo URL e configuralo nella tua Dashboard Paypercut in Sviluppatori > Webhook';
$_['help_logging'] = 'Abilita la registrazione di richieste API, eventi webhook ed errori. Disabilita in produzione a meno che non sia necessario il debug. I log possono contenere dati sensibili.';
$_['help_payment_method_config'] = 'Seleziona una configurazione del metodo di pagamento (profilo di pagamento) per controllare quali metodi di pagamento sono disponibili per i clienti. Lasciare vuoto per utilizzare il predefinito.';

// Error
$_['error_permission'] = 'Attenzione: Non hai i permessi per modificare il modulo di pagamento Paypercut!';
$_['error_api_key'] = 'Chiave API obbligatoria!';
$_['error_statement_descriptor'] = 'La descrizione dell\'estratto conto deve essere di massimo 22 caratteri!';
$_['error_unsupported_currency'] = 'Attenzione: La valuta del tuo negozio (%s) non è supportata da Paypercut. Valute supportate: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Modalità Test';
$_['text_mode_live'] = 'Modalità Live';
$_['text_mode_unknown'] = 'Modalità Sconosciuta';
$_['text_enabled'] = 'Abilitato';
$_['text_disabled'] = 'Disabilitato';
$_['text_hosted'] = 'Hosted (Reindirizzamento)';
$_['text_embedded'] = 'Embedded (Sul sito)';
$_['text_statement_preview'] = 'Anteprima';
$_['text_webhook_info'] = 'Configura questo URL webhook nella tua <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Dashboard Paypercut</a>';
$_['text_webhook_configured'] = 'Webhook configurato e attivo';
$_['text_webhook_not_configured'] = 'Webhook non configurato';
$_['text_webhook_create'] = 'Crea Webhook Automaticamente';
$_['text_webhook_delete'] = 'Elimina Webhook';
$_['text_webhook_creating'] = 'Creazione webhook in corso...';
$_['text_webhook_deleting'] = 'Eliminazione webhook in corso...';
$_['text_wallet_settings'] = 'Impostazioni Wallet';
$_['text_testing_connection'] = 'Test connessione in corso...';
$_['text_connection_success'] = 'Connessione riuscita!';
$_['text_connection_failed'] = 'Connessione fallita';

// Button
$_['button_test_connection'] = 'Testa Connessione';
