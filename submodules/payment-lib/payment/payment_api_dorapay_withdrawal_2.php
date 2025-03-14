<?php
require_once dirname(__FILE__) . '/payment_api_dorapay_withdrawal.php';

/**
 * DORAPAY  取款
 *
 * * DORAPAY_WITHDRAWAL_2_PAYMENT_API, ID: 881
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.dorapay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dorapay_withdrawal_2 extends Payment_api_dorapay_withdrawal {

	const PAY_RESULT_SUCCESS = '0';

	public function getPlatformCode() {
		return DORAPAY_WITHDRAWAL_2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dorapay_withdrawal_2';
	}

}
