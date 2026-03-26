<?php
// Heading
$_['heading_title'] = 'Paypercut Zahlungsgateway';

// Text
$_['text_extension'] = 'Erweiterungen';
$_['text_success'] = 'Erfolg: Sie haben das Paypercut-Zahlungsmodul erfolgreich geändert!';
$_['text_edit'] = 'Paypercut bearbeiten';
$_['text_paypercut'] = '<img src="view/image/payment/paypercut.png" alt="Paypercut" title="Paypercut" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_api_key'] = 'API-Schlüssel';
$_['entry_operating_account'] = 'Betriebskonto-ID';
$_['entry_statement_descriptor'] = 'Kontoauszugsbeschreibung';
$_['entry_google_pay'] = 'Google Pay';
$_['entry_apple_pay'] = 'Apple Pay';
$_['entry_checkout_mode'] = 'Checkout-Modus';
$_['entry_webhook_url'] = 'Webhook-URL';
$_['entry_order_status'] = 'Bestellstatus';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sortierreihenfolge';
$_['entry_logging'] = 'Protokollierung aktivieren';
$_['entry_payment_method_config'] = 'Zahlungsmethoden-Konfiguration';

// Help
$_['help_api_key'] = 'Geben Sie Ihren Paypercut-API-Schlüssel aus dem Dashboard ein';
$_['help_operating_account'] = 'Geben Sie Ihre Betriebskonto-ID ein (zu finden im Paypercut-Dashboard)';
$_['help_statement_descriptor'] = 'Text, der auf dem Kontoauszug des Kunden erscheint (max. 22 Zeichen). Leer lassen, um Standard zu verwenden.';
$_['help_google_pay'] = 'Google Pay als Zahlungsoption aktivieren';
$_['help_apple_pay'] = 'Apple Pay als Zahlungsoption aktivieren';
$_['help_checkout_mode'] = 'Wählen Sie zwischen gehostet (Weiterleitung zur Paypercut-Seite) oder eingebettet (Checkout auf Ihrer Website)';
$_['help_webhook_url'] = 'Kopieren Sie diese URL und konfigurieren Sie sie in Ihrem Paypercut-Dashboard unter Entwickler > Webhooks';
$_['help_logging'] = 'Protokollierung von API-Anfragen, Webhook-Ereignissen und Fehlern aktivieren. Im Produktivbetrieb deaktivieren, es sei denn, Sie debuggen. Protokolle können sensible Daten enthalten.';
$_['help_payment_method_config'] = 'Wählen Sie eine Zahlungsmethoden-Konfiguration (Zahlungsprofil), um zu steuern, welche Zahlungsmethoden den Kunden zur Verfügung stehen. Leer lassen, um Standard zu verwenden.';

// Error
$_['error_permission'] = 'Warnung: Sie haben keine Berechtigung, das Paypercut-Zahlungsmodul zu ändern!';
$_['error_api_key'] = 'API-Schlüssel erforderlich!';
$_['error_statement_descriptor'] = 'Kontoauszugsbeschreibung darf maximal 22 Zeichen lang sein!';
$_['error_unsupported_currency'] = 'Warnung: Ihre Shop-Währung (%s) wird von Paypercut nicht unterstützt. Unterstützte Währungen: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON';

// Text
$_['text_mode_test'] = 'Testmodus';
$_['text_mode_live'] = 'Live-Modus';
$_['text_mode_unknown'] = 'Unbekannter Modus';
$_['text_enabled'] = 'Aktiviert';
$_['text_disabled'] = 'Deaktiviert';
$_['text_hosted'] = 'Gehostet (Weiterleitung)';
$_['text_embedded'] = 'Eingebettet (Vor Ort)';
$_['text_statement_preview'] = 'Vorschau';
$_['text_webhook_info'] = 'Konfigurieren Sie diese Webhook-URL in Ihrem <a href="https://dashboard.paypercut.io/developers/webhooks" target="_blank">Paypercut-Dashboard</a>';
$_['text_webhook_configured'] = 'Webhook ist konfiguriert und aktiv';
$_['text_webhook_not_configured'] = 'Webhook nicht konfiguriert';
$_['text_webhook_create'] = 'Webhook automatisch erstellen';
$_['text_webhook_delete'] = 'Webhook löschen';
$_['text_webhook_creating'] = 'Webhook wird erstellt...';
$_['text_webhook_deleting'] = 'Webhook wird gelöscht...';
$_['text_wallet_settings'] = 'Wallet-Einstellungen';
$_['text_testing_connection'] = 'Verbindung wird getestet...';
$_['text_connection_success'] = 'Verbindung erfolgreich!';
$_['text_connection_failed'] = 'Verbindung fehlgeschlagen';

// Button
$_['button_test_connection'] = 'Verbindung testen';
