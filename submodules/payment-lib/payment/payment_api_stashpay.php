<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stashpay.php';

/**
 * STASHPAY
 *
 * * STASHPAY_PAYMENT_API, ID: 6418
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_stashpay extends Abstract_payment_api_stashpay {

    public function getPlatformCode() {
        return STASHPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'stashpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}