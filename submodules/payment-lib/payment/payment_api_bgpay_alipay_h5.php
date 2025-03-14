<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bgpay.php';

/**
 * BGPAY 聚合支付
 * *
 * * BGPAY_ALIPAY_H5_PAYMENT_API, ID: 942
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.1115187.com/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bgpay_alipay_h5 extends Abstract_payment_api_bgpay {

    public function getPlatformCode() {
        return BGPAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bgpay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['fxpay'] = self::FXPAY_ALIPAY_H5;
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
