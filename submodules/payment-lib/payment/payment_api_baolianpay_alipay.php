<?php
require_once dirname(__FILE__) . '/payment_api_bingopay_alipay.php';
/**
 * BAOLIANPAY  宝联付 deposit 支付寶 繼承bingopay
 * 
 *
 * BAOLIANPAY_ALIPAY_PAYMENT_API, ID: 5044
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
class Payment_api_baolianpay_alipay extends Payment_api_bingopay_alipay {

	public function getPlatformCode() {
		return BAOLIANPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'baolianpay_alipay';
	}

}
