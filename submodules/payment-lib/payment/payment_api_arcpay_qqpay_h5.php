<?php
require_once dirname(__FILE__) . '/abstract_payment_api_arcpay.php';

/**
 * ARCPAY 大强
 * *
 * * ARCPAY_QQPAY_H5_PAYMENT_API, ID: 900
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.arcpay.info/gateway/payApi/PayApiController/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_arcpay_qqpay_h5 extends Abstract_payment_api_arcpay {

    public function getPlatformCode() {
        return ARCPAY_QQPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'arcpay_qqpay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_QQPAY_H5;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
