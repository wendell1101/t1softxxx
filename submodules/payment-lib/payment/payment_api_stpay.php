<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stpay.php';

/**
 *
 * * stpay_PAYMENT_API, ID: 5968
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://shanghu.zkhfnm27.stpay01.com:18697
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_stpay extends Abstract_payment_api_stpay {

    public function getPlatformCode() {
        return STPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'stpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = self::PAY_METHOD_ALIPAY;
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
