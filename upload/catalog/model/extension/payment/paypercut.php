<?php
class ModelExtensionPaymentPaypercut extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/paypercut');

        $status = true;

        // Check if currency is supported
        $currency = $this->session->data['currency'];
        if (!$this->isCurrencySupported($currency)) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'paypercut',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_paypercut_sort_order')
            );
        }

        return $method_data;
    }

    /**
     * Check if the provided currency is supported by Paypercut
     */
    private function isCurrencySupported($currency_code)
    {
        $supported_currencies = array('BGN', 'DKK', 'SEK', 'NOK', 'GBP', 'EUR', 'USD', 'CHF', 'CZK', 'HUF', 'PLN', 'RON');
        return in_array(strtoupper($currency_code), $supported_currencies);
    }
}
