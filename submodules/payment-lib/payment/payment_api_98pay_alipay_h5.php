<?php
require_once dirname(__FILE__) . '/abstract_payment_api_98pay.php';

/**
 * 98PAY
 *
 * * _98PAY_ALIPAY_H5_PAYMENT_API, ID: 971
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.yduma.cn/pay/api/api.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_98pay_alipay_h5 extends Abstract_payment_api_98pay {

    public function getPlatformCode() {
        return _98PAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return '98pay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['code_type'] = self::CODE_TYPE_ALIPAY;
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