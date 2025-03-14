<?php
require_once dirname(__FILE__) . '/payment_api_smartpay_withdrawal.php';
/**
 * SMARTPAY
 *
 * * SMARTPAY_WITHDRAWAL_3_PAYMENT_API, ID: 5694
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
class Payment_api_smartpay_withdrawal_3 extends Payment_api_smartpay_withdrawal {

    public function getPlatformCode() {
        return SMARTPAY_WITHDRAWAL_3_PAYMENT_API;
    }

    public function getPrefix() {
        return 'smartpay_withdrawal_3';
    }
}