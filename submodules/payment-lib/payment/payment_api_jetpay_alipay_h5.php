<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jetpay.php';
/**
 * JetPay 捷智付
 *
 * * JETPAY_ALIPAY_H5_PAYMENT_API, ID: 5194
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://39.98.88.140:8082/pp_server/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jetpay_alipay_h5 extends Abstract_payment_api_jetpay {

    public function getPlatformCode() {
        return JETPAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'jetpay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::SCANTYPE_ALIPAY;
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
