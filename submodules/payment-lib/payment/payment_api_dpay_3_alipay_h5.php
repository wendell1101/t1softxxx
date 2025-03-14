<?php
require_once dirname(__FILE__) . '/payment_api_dpay_alipay_h5.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_3_ALIPAY_H5_PAYMENT_API, ID: 5368
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://a.85415.com:919/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_3_alipay_h5 extends Payment_api_dpay_alipay_h5 {

    public function getPlatformCode() {
        return DPAY_3_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dpay_3_alipay_h5';
    }
}
