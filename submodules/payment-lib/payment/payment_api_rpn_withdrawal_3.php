<?php
require_once dirname(__FILE__) . '/payment_api_rpn_withdrawal.php';

/**
 * RPN
 *
 *
 * * RPN_WITHDRAWAL_3_PAYMENT_API, ID: 856
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://withdrawal-api.rpnpay.com/payout.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_withdrawal_3 extends Payment_api_rpn_withdrawal {
	public function getPlatformCode() {
		return RPN_WITHDRAWAL_3_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn_withdrawal_3';
	}
}