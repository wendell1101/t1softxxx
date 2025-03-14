<?php
require_once dirname(__FILE__) . '/payment_api_bingopay_jdpay.php';
/**
 * BAOLIANPAY  宝联付 deposit 京东 繼承bingopay
 * 
 *
 * BAOLIANPAY_JDPAY_PAYMENT_API, ID: 5046
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
class Payment_api_baolianpay_jdpay extends Payment_api_bingopay_jdpay {

	public function getPlatformCode() {
		return BAOLIANPAY_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'baolianpay_jdpay';
	}

}