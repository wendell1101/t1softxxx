<?php
require_once dirname(__FILE__) . '/payment_api_today_withdrawal.php';

/**
 * today3
 *
 * * TODAY3_WITHDRAWAL_PAYMENT_API, ID: 6155
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_today3_withdrawal extends Payment_api_today_withdrawal {

    public function getPlatformCode() {
        return TODAY3_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'today3_withdrawal';
    }

}
