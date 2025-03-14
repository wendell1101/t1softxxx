<?php
require_once dirname(__FILE__) . '/payment_api_dpay_unionpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_3_UNIONPAY_PAYMENT_API, ID: 5371
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
class Payment_api_dpay_3_unionpay extends Payment_api_dpay_unionpay {

    public function getPlatformCode() {
        return DPAY_3_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dpay_3_unionpay';
    }
}
