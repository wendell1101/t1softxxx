<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ipay.php';

/**
 * ipay
 *
 * * IPAY_PAYMENT_API, ID: 6082
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://hub.thepasjg.com/order/create
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ipay extends Abstract_payment_api_ipay {

    public function getPlatformCode() {
        return IPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::DEPOSIT_CHANNEL_BANK;
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
