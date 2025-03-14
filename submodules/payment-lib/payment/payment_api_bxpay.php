<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bxpay.php';

/**
 * bxpay
 *
 * * BXPAY_PAYMENT_API, ID: 6243
 *
 * Field Values:
 * * URL: https://pay.baxizhifu.com/api/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_bxpay extends Abstract_payment_api_bxpay {

    public function getPlatformCode() {
        return BXPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bxpay';
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