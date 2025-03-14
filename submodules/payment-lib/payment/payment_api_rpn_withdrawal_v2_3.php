<?php
require_once dirname(__FILE__) . '/payment_api_rpn_withdrawal_v2.php';
/**
 * RPN
 *
 * * RPN_WITHDRAWAL_V2_PAYMENT_API, ID: 5142
 * * RPN_WITHDRAWAL_V2_2_PAYMENT_API, ID: 5143
 * * RPN_WITHDRAWAL_V2_3_PAYMENT_API, ID: 5144
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://query.rpnpay.com/payout.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_withdrawal_v2_3 extends Payment_api_rpn_withdrawal_v2 {
    public function getPlatformCode() {
        return RPN_WITHDRAWAL_V2_3_PAYMENT_API;
    }

    public function getPrefix() {
        return 'rpn_withdrawal_v2_3';
    }
}