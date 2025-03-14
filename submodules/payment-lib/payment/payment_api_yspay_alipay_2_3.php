<?php
require_once dirname(__FILE__) . '/payment_api_yspay_alipay_2.php';

/**
 * YSPAY 广州银商/贝付 - 支付宝
 *
 *
 * YSPAY_ALIPAY_2_3_PAYMENT_API, ID: 871
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_Info
 *
 * Field Values:
 * * URL: http://www.xshuyu.com/pay/api.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra_Info: {"yspay_channel" : "## zftd code ##","yspay_mobile_channel" : "## zftd code ##",}
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yspay_alipay_2_3 extends Payment_api_yspay_alipay_2 {

    public function getPlatformCode() {
        return YSPAY_ALIPAY_2_3_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yspay_alipay_2_3';
    }
}
