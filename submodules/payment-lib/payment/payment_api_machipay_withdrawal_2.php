<?php
require_once dirname(__FILE__) . '/payment_api_machipay_withdrawal.php';

/**
 * MACHIPAY 麻吉支付
 *
 * * MACHIPAY_WITHDRAWAL_2_PAYMENT_API, ID: 5531
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: http://paygate.machi-tech.com:9090/gateway-onl/txn
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_machipay_withdrawal_2 extends Payment_api_machipay_withdrawal {

	public function getPlatformCode() {
		return MACHIPAY_WITHDRAWAL_2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'machipay_withdrawal_2';
	}
}
