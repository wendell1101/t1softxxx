<?php
require_once dirname(__FILE__) . '/payment_api_huanyu_alipay.php';

/**
 * HUANYU 寰宇
 *
 * * HUANYU_ALIPAY_4_PAYMENT_API, ID: 5206
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huanyu_alipay_4 extends Payment_api_huanyu_alipay {

    public function getPlatformCode() {
        return HUANYU_ALIPAY_4_PAYMENT_API;
    }

    public function getPrefix() {
        return 'huanyu_alipay_4';
    }
}