<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cashpay_v2.php';
/**
 *
 * * cashpay_PAYMENT_API, ID: 6592
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ib.brazil-pix.com/open-api/pay/payment
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_cashpay_v2 extends Abstract_payment_api_cashpay_v2 {

    public function getPlatformCode() {
        return CASHPAY_V2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cashpay_v2';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}