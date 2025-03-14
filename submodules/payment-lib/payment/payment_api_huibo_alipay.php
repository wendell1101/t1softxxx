<?php
require_once dirname(__FILE__) . '/payment_api_huibo_qrcode.php';

/**
 * HuiBo 汇博支付
 *
 * HUIBO_ALIPAY_PAYMENT_API, ID: 118
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://47.90.92.130:9899/HBConn/online
 * * Account: ## Merchant Account ##
 * * Extra Info
 * > {
 * >     "huibo_api_url" : "http://47.90.92.130:9899/HBConn/LFT",
 * >     "huibo_priv_key": "## path to merchant's private key ##",
 * >     "huibo_pub_key" : "## path to merchant's public key ##",
 * >     "huibo_api_pub_key" : "## path to API's public key ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huibo_alipay extends Payment_api_huibo_qrcode {
	public function getPlatformCode() {
		return HUIBO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huibo_alipay';
	}

	protected function getChannelCode() {
		return 'ALIPAY';
	}
}