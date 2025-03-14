<?php
require_once dirname(__FILE__) . '/payment_api_aipay_withdrawal.php';

/**
 * aipay 艾付 取款
 *
 * * AIPAY_2_WITHDRAWAL_PAYMENT_API, ID: 5318
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.goodatpay.com/withdraw/singleWithdraw
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aipay_2_withdrawal extends Payment_api_aipay_withdrawal {

	public function getPlatformCode() {
		return AIPAY_2_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'aipay_2_withdrawal';
	}
}
