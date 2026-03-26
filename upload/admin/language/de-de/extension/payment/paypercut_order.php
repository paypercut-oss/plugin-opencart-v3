<?php
// Headings
$_['heading_title']          = 'Paypercut Zahlungsinformationen';

// Text
$_['text_payment_info']      = 'Zahlungsinformationen';
$_['text_transaction_id']    = 'Transaktions-ID';
$_['text_payment_status']    = 'Zahlungsstatus';
$_['text_payment_method']    = 'Zahlungsmethode';
$_['text_amount']            = 'Betrag';
$_['text_customer_id']       = 'Paypercut Kunden-ID';
$_['text_payment_date']      = 'Zahlungsdatum';
$_['text_view_dashboard']    = 'Im Dashboard anzeigen';
$_['text_refund_management'] = 'Erstattungsverwaltung';
$_['text_refund_history']    = 'Erstattungsverlauf';
$_['text_total_refunded']    = 'Gesamt erstattet';
$_['text_refund_type']       = 'Erstattungstyp';
$_['text_partial_refund']    = 'Teilerstattung';
$_['text_full_refund']       = 'Vollständige Erstattung';
$_['text_refund_amount']     = 'Erstattungsbetrag';
$_['text_refund_reason']     = 'Grund (Optional)';
$_['text_maximum_refundable'] = 'Maximal erstattbar';
$_['text_process_refund']    = 'Erstattung verarbeiten';
$_['text_fully_refunded']    = 'Diese Zahlung wurde vollständig erstattet.';
$_['text_no_transaction']    = 'Keine Paypercut-Transaktion für diese Bestellung gefunden.';
$_['text_view_details']      = 'Vollständige Transaktionsdetails anzeigen';
$_['text_capture_payment']   = 'Zahlung erfassen';
$_['text_cancel_payment']    = 'Zahlung stornieren';
$_['text_payment_authorization'] = 'Zahlungsautorisierung';
$_['text_requires_capture']  = 'Diese Zahlung wurde autorisiert, aber noch nicht erfasst. Sie müssen sie erfassen oder stornieren.';

// Transaction Details
$_['text_payment_info']      = 'Zahlungsinformationen';
$_['text_fees']              = 'Gebühren';
$_['text_net_amount']        = 'Nettobetrag';
$_['text_captured']          = 'Erfasst';
$_['text_capture_before']    = 'Erfassen vor';
$_['text_3ds_auth']          = '3D-Secure-Authentifizierung';
$_['text_authenticated']     = 'Authentifiziert';
$_['text_3ds_version']       = '3DS-Version';
$_['text_3ds_result']        = 'Ergebnis';
$_['text_payment_method_details'] = 'Zahlungsmethoden-Details';

// Status Labels
$_['text_status_succeeded']  = 'Erfolgreich';
$_['text_status_pending']    = 'Ausstehend';
$_['text_status_failed']     = 'Fehlgeschlagen';

// Success Messages
$_['success_refund']         = 'Erstattung erfolgreich verarbeitet!';
$_['success_capture']        = 'Zahlung erfolgreich erfasst!';
$_['success_cancel']         = 'Zahlung erfolgreich storniert!';

// Error Messages
$_['error_permission']       = 'Warnung: Sie haben keine Berechtigung, Erstattungen zu verarbeiten!';
$_['error_order_id']         = 'Fehler: Bestell-ID ist erforderlich.';
$_['error_transaction']      = 'Fehler: Keine Paypercut-Transaktion für diese Bestellung gefunden.';
$_['error_payment_status']   = 'Fehler: Zahlung mit Status kann nicht erstattet werden: %s';
$_['error_invalid_amount']   = 'Fehler: Ungültiger Erstattungsbetrag.';
$_['error_exceeds_available'] = 'Fehler: Erstattungsbetrag überschreitet verfügbaren Saldo.';
$_['error_api_request']      = 'Fehler: Erstattung konnte nicht verarbeitet werden. API-Fehler: %s';
$_['error_connection']       = 'Fehler: Verbindungs-Timeout bei der Verarbeitung der Erstattung.';
$_['error_unknown']          = 'Fehler: Ein unbekannter Fehler ist bei der Verarbeitung der Erstattung aufgetreten.';
$_['error_cannot_capture']   = 'Fehler: Zahlung kann nicht erfasst werden. Aktueller Status: %s';
$_['error_cannot_cancel']    = 'Fehler: Zahlung kann nicht storniert werden. Aktueller Status: %s';
$_['error_capture_failed']   = 'Fehler: Zahlung konnte nicht erfasst werden.';
$_['error_cancel_failed']    = 'Fehler: Zahlung konnte nicht storniert werden.';

// Help Text
$_['help_refund_amount']     = 'Geben Sie den zu erstattenden Betrag ein. Darf den verbleibenden Saldo nicht überschreiten.';
$_['help_refund_reason']     = 'Optional: Geben Sie einen Grund für diese Erstattung an (zur internen Referenz).';
