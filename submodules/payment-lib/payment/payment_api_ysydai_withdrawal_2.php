<?php
require_once dirname(__FILE__) . '/payment_api_ysydai_withdrawal.php';

/**
 * YSYDAI
 *
 * * YSYDAI_WITHDRAWAL_2_PAYMENT_API, ID: 5268
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paypaul.385mall.top/onlinepay/agentTransfer
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ysydai_withdrawal_2 extends Payment_api_ysydai_withdrawal {

    public function getPlatformCode() {
        return YSYDAI_WITHDRAWAL_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ysydai_withdrawal_2';
    }
}