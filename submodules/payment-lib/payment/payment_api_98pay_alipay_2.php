<?php
require_once dirname(__FILE__) . '/payment_api_98pay_alipay.php';

/**
 * 98PAY
 *
 * * _98PAY_2_ALIPAY_PAYMENT_API, ID: 5082
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
class Payment_api_98pay_alipay_2 extends Payment_api_98pay_alipay {

    public function getPlatformCode() {
        return _98PAY_2_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return '98pay_alipay_2';
    }
}