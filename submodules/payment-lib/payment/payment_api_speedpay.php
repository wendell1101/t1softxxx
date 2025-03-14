<?php
require_once dirname(__FILE__) . '/abstract_payment_api_speedpay.php';
/**
 * SPEEDPAY 快付
 *
 * * SPEEDPAY_PAYMENT_API, ID: 5915
 *
 * Required Fields:
 * * URL
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: https://api.speedpay123.com/pay
 * * Key: ## Live ID ##
 * * Secret: ## Secret Key ##
 *
 * @see         abstract_payment_api_speedpay.php
 * @category    Payment
 * @copyright   2022 tot
 */
class Payment_api_speedpay extends Abstract_payment_api_speedpay {

    public function getPlatformCode() {
        return SPEEDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'speedpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type_group']   = self::PAY_TYPE_GROUP_CARD;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}