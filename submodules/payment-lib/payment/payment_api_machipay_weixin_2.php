<?php
require_once dirname(__FILE__) . '/payment_api_machipay_weixin.php';

/**
 * MACHIPAY
 * https://mer.fastpay-technology.com/powerpay-mer/
 *
 * * MACHIPAY_WEIXIN_2_PAYMENT_API, ID: 5260
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paygate.fastpay-technology.com/powerpay-gateway-onl/txn
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_machipay_weixin_2 extends Payment_api_machipay_weixin {

    public function getPlatformCode() {
        return MACHIPAY_WEIXIN_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'machipay_weixin_2';
    }
}
