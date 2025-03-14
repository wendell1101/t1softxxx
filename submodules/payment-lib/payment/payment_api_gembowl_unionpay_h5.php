<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gembowl.php';

/**
 * GEMBOWL 聚宝盆
 * *
 * * GEMBOWL_UNIONPAY_H5_PAYMENT_API, ID: 5490
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.gembowlcenter.com/gateway.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gembowl_unionpay_h5 extends Abstract_payment_api_gembowl {

    public function getPlatformCode() {
        return GEMBOWL_UNIONPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gembowl_unionpay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::CHANNEL_CODE_UNIONPAY;
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
