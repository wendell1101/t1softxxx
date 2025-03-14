<?php
require_once dirname(__FILE__) . '/abstract_payment_api_v8pay.php';
/**
 * V8PAY
 *
 * * V8PAY_PAYMENT_API, ID: 6090
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://v8pay.com/api/fundin/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_v8pay extends Abstract_payment_api_v8pay {

    public function getPlatformCode() {
        return V8PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'v8pay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}