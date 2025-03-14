<?php
require_once dirname(__FILE__) . '/abstract_payment_api_smartpay_thirdparty.php';
/**
 * SMARTPAY_THIRDPARTY
 *
 * * SMARTPAY_THIRDPARTY_PAYMENT_API, ID: 5738
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_smartpay_thirdparty extends Abstract_payment_api_smartpay_thirdparty {

    public function getPlatformCode() {
        return SMARTPAY_THIRDPARTY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'smartpay_thirdparty';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormForRedirect($params);
    }
}