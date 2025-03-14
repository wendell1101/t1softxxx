<?php
require_once dirname(__FILE__) . '/abstract_payment_api_iq.php';

/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_PAYSAFECARD_PAYMENT_API, ID: 5562
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq/api/paysafecard/deposit/process
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_iq_paysafecard extends Abstract_payment_api_iq {

    public function getPlatformCode() {
        return IQ_PAYSAFECARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'iq_paysafecard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params, $orderId) {
        return $this->processPaymentUrlFormForRedirect($params, $orderId);
    }
}
