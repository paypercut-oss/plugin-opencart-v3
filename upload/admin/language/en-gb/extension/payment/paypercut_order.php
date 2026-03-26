<?php
// Headings
$_['heading_title']          = 'Paypercut Payment Information';

// Text
$_['text_payment_info']      = 'Payment Information';
$_['text_transaction_id']    = 'Transaction ID';
$_['text_payment_status']    = 'Payment Status';
$_['text_payment_method']    = 'Payment Method';
$_['text_amount']            = 'Amount';
$_['text_customer_id']       = 'Paypercut Customer ID';
$_['text_payment_date']      = 'Payment Date';
$_['text_view_dashboard']    = 'View in Dashboard';
$_['text_refund_management'] = 'Refund Management';
$_['text_refund_history']    = 'Refund History';
$_['text_total_refunded']    = 'Total Refunded';
$_['text_refund_type']       = 'Refund Type';
$_['text_partial_refund']    = 'Partial Refund';
$_['text_full_refund']       = 'Full Refund';
$_['text_refund_amount']     = 'Refund Amount';
$_['text_refund_reason']     = 'Reason (Optional)';
$_['text_maximum_refundable'] = 'Maximum refundable';
$_['text_process_refund']    = 'Process Refund';
$_['text_fully_refunded']    = 'This payment has been fully refunded.';
$_['text_no_transaction']    = 'No Paypercut transaction found for this order.';
$_['text_view_details']      = 'View Full Transaction Details';
$_['text_capture_payment']   = 'Capture Payment';
$_['text_cancel_payment']    = 'Cancel Payment';
$_['text_payment_authorization'] = 'Payment Authorization';
$_['text_requires_capture']  = 'This payment has been authorized but not yet captured. You must capture or cancel it.';

// Transaction Details
$_['text_payment_info']      = 'Payment Information';
$_['text_fees']              = 'Fees';
$_['text_net_amount']        = 'Net Amount';
$_['text_captured']          = 'Captured';
$_['text_capture_before']    = 'Capture Before';
$_['text_3ds_auth']          = '3D Secure Authentication';
$_['text_authenticated']     = 'Authenticated';
$_['text_3ds_version']       = '3DS Version';
$_['text_3ds_result']        = 'Result';
$_['text_payment_method_details'] = 'Payment Method Details';

// Status Labels
$_['text_status_succeeded']  = 'Succeeded';
$_['text_status_pending']    = 'Pending';
$_['text_status_failed']     = 'Failed';

// Success Messages
$_['success_refund']         = 'Refund processed successfully!';
$_['success_capture']        = 'Payment captured successfully!';
$_['success_cancel']         = 'Payment canceled successfully!';

// Error Messages
$_['error_permission']       = 'Warning: You do not have permission to process refunds!';
$_['error_order_id']         = 'Error: Order ID is required.';
$_['error_transaction']      = 'Error: No Paypercut transaction found for this order.';
$_['error_payment_status']   = 'Error: Cannot refund payment with status: %s';
$_['error_invalid_amount']   = 'Error: Invalid refund amount.';
$_['error_exceeds_available'] = 'Error: Refund amount exceeds available balance.';
$_['error_api_request']      = 'Error: Failed to process refund. API error: %s';
$_['error_connection']       = 'Error: Connection timeout while processing refund.';
$_['error_unknown']          = 'Error: An unknown error occurred while processing the refund.';
$_['error_cannot_capture']   = 'Error: Payment cannot be captured. Current status: %s';
$_['error_cannot_cancel']    = 'Error: Payment cannot be canceled. Current status: %s';
$_['error_capture_failed']   = 'Error: Failed to capture payment.';
$_['error_cancel_failed']    = 'Error: Failed to cancel payment.';

// Help Text
$_['help_refund_amount']     = 'Enter the amount to refund. Must not exceed the remaining balance.';
$_['help_refund_reason']     = 'Optional: Provide a reason for this refund (for internal reference).';
