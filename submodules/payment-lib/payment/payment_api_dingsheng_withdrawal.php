<?php
require_once dirname(__FILE__) . '/payment_api_eboo_withdrawal.php';

/**
 * DINGSHENG 鼎盛支付
 * 
 * * DINGSHENG_WITHDRAWAL_PAYMENT_API, ID: 5524
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://gtpay-2.nds966.com/Payment_Dfpay_add.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dingsheng_withdrawal extends Payment_api_eboo_withdrawal {

    public function getPlatformCode() {
        return DINGSHENG_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dingsheng_withdrawal';
    }
}
