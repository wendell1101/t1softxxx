<?php
require_once dirname(__FILE__) . '/payment_api_today.php';

/**
 *
 * TODAY2
 *
 * * 'TODAY2_PAYMENT_API', ID 6152
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_today2 extends Payment_api_today {

	public function getPlatformCode() {
		return TODAY2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'today2';
	}
}
