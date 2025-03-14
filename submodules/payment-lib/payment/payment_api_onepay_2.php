<?php
require_once dirname(__FILE__) . '/payment_api_onepay.php';
/**
 * ONEPAY
 *
 * * ONEPAY_2_PAYMENT_API, ID: 5315
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.onepay.solutions/payment/v3/checkOut.html
 * * Extra Info:
 * > {
 * >    "onepay_priv_key": "## Private Key ##",
 * >    "onepay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_onepay_2 extends Payment_api_onepay {

    public function getPlatformCode() {
        return ONEPAY_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'onepay_2';
    }
}
