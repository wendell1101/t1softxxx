<?php
require_once dirname(__FILE__) . '/payment_api_yingsheng_alipay.php';
/**
 * YINGSHENG 盈盛
 *
 * * YINGSHENG_ALIPAY_3_PAYMENT_API, ID:5707
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: https://api.yoopayment.com/rsa/deposit
 * * Extra Info:
 * > {
 * >    "yingsheng_priv_key": "## Private Key ##",
 * >    "yingsheng_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yingsheng_alipay_3 extends Payment_api_yingsheng_alipay {

    public function getPlatformCode() {
        return YINGSHENG_ALIPAY_3_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yingsheng_alipay_3';
    }

   
}
