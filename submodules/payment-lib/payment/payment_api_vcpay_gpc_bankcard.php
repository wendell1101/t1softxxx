<?php
require_once dirname(__FILE__) . '/payment_api_vcpay_gpc.php';

/**
 *
 *
 * VCPAY_GPC_BANKCARD_PAYMENT_API, ID: 5323
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://xxxx.com/Home/Jiekou/getParameterFromOther
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vcpay_gpc_bankcard extends Payment_api_vcpay_gpc {

	public function getPlatformCode() {
		return VCPAY_GPC_BANKCARD_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vcpay_gpc_bankcard';
	}
}
