<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paysafe.php';
/**
 * PAYSAFE
 *
 * * PAYSAFE_CREDITCARD_PAYMENT_API, ID: 5012
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payment.cdc.alogateway.co/ChinaDebitCard
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paysafe_creditcard extends Abstract_payment_api_paysafe {

    public function getPlatformCode() {
        return PAYSAFE_CREDITCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paysafe_creditcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
