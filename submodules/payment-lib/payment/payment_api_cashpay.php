<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cashpay.php';
/**
 *
 * * cashpay_PAYMENT_API, ID: 6186
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payment.dev.mspays.xyz/haoli711/orders/v3/scan
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cashpay extends Abstract_payment_api_cashpay {

    public function getPlatformCode() {
        return CASHPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cashpay';
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