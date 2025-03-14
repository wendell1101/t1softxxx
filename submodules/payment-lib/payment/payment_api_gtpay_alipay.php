<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gtpay.php';

/**
 * GTPAY
 * *
 * * GTPAY_ALIPAY_PAYMENT_API, ID: 5376
 *
 * Required Fields:
 * * URL: 
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gtpay_alipay extends Abstract_payment_api_gtpay {

    public function getPlatformCode() {
        return GTPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gtpay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_ALIPAY;
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
