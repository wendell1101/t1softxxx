<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stonpay.php';

/**
 *
 * * STONPAY_ALIPAY_H5_PAYMENT_API', ID: 5085
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_stonpay_alipay_h5 extends Abstract_payment_api_stonpay {

    public function getPlatformCode() {
        return STONPAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'stonpay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pType'] = self::PTYPE_ALIPAY_H5;
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
