<?php
require_once dirname(__FILE__) . '/payment_api_daddypay_withdrawal.php';

/**
 *
 * DaddyPay Withdrawal 出款 JBP 聚宝盆
 *
 * JBP_WITHDRAWAL_PAYMENT_API, ID: 5001
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jbp_withdrawal extends Payment_api_daddypay_withdrawal {

	public function getPlatformCode() {
		return JBP_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jbp_withdrawal';
	}

}
