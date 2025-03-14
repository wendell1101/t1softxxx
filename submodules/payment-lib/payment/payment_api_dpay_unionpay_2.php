<?php
require_once dirname(__FILE__) . '/payment_api_dpay_unionpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_2_UNIONPAY_PAYMENT_API, ID: 5127
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.273787.cn/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_unionpay_2 extends Payment_api_dpay_unionpay {

    public function getPlatformCode() {
        return DPAY_2_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dpay_unionpay_2';
    }
}
