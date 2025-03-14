<?php
require_once dirname(__FILE__) . '/payment_api_dpay_unionpay_onlinebank.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_2_UNIONPAY_ONLINEBANK_PAYMENT_API, ID: 5128
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
class Payment_api_dpay_unionpay_onlinebank_2 extends Payment_api_dpay_unionpay_onlinebank {

    public function getPlatformCode() {
        return DPAY_2_UNIONPAY_ONLINEBANK_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dpay_unionpay_onlinebank_2';
    }
}
