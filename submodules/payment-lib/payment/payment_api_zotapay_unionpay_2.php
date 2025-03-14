<?php
require_once dirname(__FILE__) . '/payment_api_zotapay_unionpay.php';

/**
 *
 * * ZOTAPAY_UNIONPAY_2_PAYMENT_API, ID: 5571
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://sandbox.zotapay.com/paynet/api/v2/sale-form/1
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zotapay_unionpay_2 extends Payment_api_zotapay_unionpay {

	public function getPlatformCode() {
		return ZOTAPAY_UNIONPAY_2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zotapay_unionpay_2';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
	}
}
