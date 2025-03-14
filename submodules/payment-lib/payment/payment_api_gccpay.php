<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gccpay.php';

/**
 * gccpay
 *
 * * GCCPAY_PAYMENT_API, ID: 6303
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
 * @copyright 2013-2023 tot
 */
class Payment_api_gccpay extends Abstract_payment_api_gccpay {

    public function getPlatformCode() {
        return GCCPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gccpay';
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
        return $this->processPaymentUrlFormPost($params);
    }
}