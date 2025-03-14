<?php
require_once dirname(__FILE__) . '/payment_api_dpay_quickpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_3_QUICKPAY_PAYMENT_API, ID: 5370
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
class Payment_api_dpay_3_quickpay extends Payment_api_dpay_quickpay {

    public function getPlatformCode() {
        return DPAY_3_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dpay_3_quickpay';
    }
}
