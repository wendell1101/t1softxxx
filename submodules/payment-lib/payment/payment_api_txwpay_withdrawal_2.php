<?php
require_once dirname(__FILE__) . '/payment_api_txwpay_withdrawal.php';

/**
 * TXWPAY  同兴旺
 *
 * * TXWPAY_WITHDRAWAL_2_PAYMENT_API, ID: 5345
 *
 * Required Fields:
 * *git  URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://27.124.8.30/Pay/GateWayPement.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_txwpay_withdrawal_2 extends Payment_api_txwpay_withdrawal {

    public function getPlatformCode() {
        return TXWPAY_WITHDRAWAL_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'txwpay_withdrawal_2';
    }
}