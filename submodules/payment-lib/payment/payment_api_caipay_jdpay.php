<?php
require_once dirname(__FILE__) . '/abstract_payment_api_caipay.php';

/**
 *
 * * CAIPAY_JDPAY_PAYMENT_API, ID: 696
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: caipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_caipay_jdpay extends Abstract_payment_api_caipay {

    public function getPlatformCode() {
        return CAIPAY_JDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'caipay_jdpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payMode'] = self::SCANTYPE_JDPAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
