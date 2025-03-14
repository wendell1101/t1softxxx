<?php
require_once dirname(__FILE__) . '/payment_api_bingopay_unionpay.php';
/**
 * BAOLIANPAY  宝联付 deposit 银联 繼承bingopay
 * 
 *
 * BAOLIANPAY_UNIONPAY_PAYMENT_API, ID: 5047
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://147.92.33.235:18888/open-gateway/trade/invoke
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_baolianpay_unionpay extends Payment_api_bingopay_unionpay {

	public function getPlatformCode() {
		return BAOLIANPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'baolianpay_unionpay';
	}

}