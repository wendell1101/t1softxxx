<?php
require_once dirname(__FILE__) . '/abstract_payment_api_keenpay.php';

/**
 * keenpay
 *
 * * KEENPAY_PAYMENT_API, ID: 6101
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.aigateway.xyz/gateway/trade/v1/rpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_keenpay extends Abstract_payment_api_keenpay {

    public function getPlatformCode() {
        return KEENPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'keenpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['service'] = self::DEPOSIT_CHANNEL_BANK;
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
