<?php
require_once dirname(__FILE__) . '/abstract_payment_api_happypay.php';

/**
 * HAPPYPAY_PAYMENT_API
 *
 * * HAPPYPAY_PAYMENT_API, ID: 5728
 *
 * Required Fields:
 * * URL
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: http://khgri4829.com:6084/api/pay/V2
 * * Key: ## pay key ##
 * * Secret: ## pay secret ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_happypay extends Abstract_payment_api_happypay {

    public function getPlatformCode() {
        return HAPPYPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'happypay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params["channleType"] = self::ONLINE_BANK_CHANNLE;
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