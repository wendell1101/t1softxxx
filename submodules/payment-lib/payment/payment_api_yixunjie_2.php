<?php
require_once dirname(__FILE__) . '/payment_api_yixunjie.php';
/**
 * YIXUNJIE 易迅捷 / GUANSHIN 广鑫
 *
 * * YIXUNJIE_2_PAYMENT_API, ID: 5244
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.244.47.216/orderpay.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yixunjie_2 extends Payment_api_yixunjie {

    public function getPlatformCode() {
        return YIXUNJIE_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yixunjie_2';
    }
}
