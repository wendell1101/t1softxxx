<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ftpay.php';

/**
 * ftpay
 *
 * * FTPAY_PAYMENT_API, ID: 6260
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://yyds68.cc/Apipay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_ftpay extends Abstract_payment_api_ftpay {

    public function getPlatformCode() {
        return FTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ftpay';
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