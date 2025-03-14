<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yunpay.php';

/**
 * YUNPAY
 *
 * * YUNPAY_PAYMENT_API, ID: 5973
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
class Payment_api_yunpay extends Abstract_payment_api_yunpay {

    public function getPlatformCode() {
        return YUNPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yunpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['PayTypeId'] = self::DEPOSIT_CHANNEL_BANK;
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
