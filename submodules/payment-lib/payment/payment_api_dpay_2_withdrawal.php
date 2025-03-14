<?php
require_once dirname(__FILE__) . '/payment_api_dpay_withdrawal.php';

/**
 * DPAY
 *
 * * DPAY_2_WITHDRAWAL_PAYMENT_API, ID: 5130
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: http://api.273787.cn/api/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_2_withdrawal extends Payment_api_dpay_withdrawal {
	public function getPlatformCode() {
		return DPAY_2_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dpay_2_withdrawal';
	}
}
