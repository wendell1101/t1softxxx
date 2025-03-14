<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eferpay.php';
/**
 * EFERPAY
 *
 * * EFERPAY_BANKCARD_PAYMENT_API, ID: 5191
 *
 * Required Fields:
 * * Account
 * * Key
 * * Secret
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.eferpay.com/oss/wallet/confpay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_eferpay_bankcard extends Abstract_payment_api_eferpay {

    public function getPlatformCode() {
        return EFERPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'eferpay_bankcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }
}
