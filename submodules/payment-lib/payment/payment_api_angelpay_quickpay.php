<?php
require_once dirname(__FILE__) . '/abstract_payment_api_angelpay.php';
/**
 * ANGELPAY
 * https://angelpay168.com
 *
 * * ANGELPAY_QUICKPAY_PAYMENT_API, ID: 5028
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://angtz.com/api/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_angelpay_quickpay extends Abstract_payment_api_angelpay {

    public function getPlatformCode() {
        return ANGELPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'angelpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channeltype'] = self::CHANNEL_QUICKPAY;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
