<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cokepay.php';
/**
 * COKEPAY 卡卡支付
 *
 * * COKEPAY_PAYMENT_API, ID: 5919
 *
 * Required Fields:
 * * URL
 * * Key    (appid)
 * * Secret (secret key)
 *
 * Field Values:
 * * URL: https://api.cokepaypal.com/index/unifiedorder
 * * Key: ## app ID ##
 * * Secret: ## Secret Key ##
 *
 * @see         abstract_payment_api_cokepay.php
 * @category    Payment
 * @copyright   2022 tot
 */
class Payment_api_cokepay extends Abstract_payment_api_cokepay {

    public function getPlatformCode() {
        return COKEPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cokepay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type']   = self::PAY_TYPE_CARDTOCARD;
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