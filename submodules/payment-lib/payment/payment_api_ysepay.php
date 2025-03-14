<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ysepay.php';

/**
 *
 * YSEPay 银盛支付
 *
 * YSEPAY_PAYMENT_API, ID: 156
 *
 * Required Fields:
 * * URL
 * * Account
 * * ExtraInfo
 *
 * Field Values:
 * * URL
 * 		- Live: https://openapi.ysepay.com/gateway.do
 * 		- Sandbox: https://mertest.ysepay.com/openapi_gateway/gateway.do
 * * Account: ## Partner ID ##
 * * ExtraInfo
 * > {
 * >	"ysepay_seller_id" : "## Seller ID ##",
 * >	"ysepay_seller_name" : "## Seller Name ##",
 * >	"ysepay_business_code" : "## Business Code ##",
 * >	"ysepay_rsa_pub_key" : "## Path to public key file ##",
 * >	"ysepay_rsa_priv_key" : "## Path to private key file ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ysepay extends Abstract_payment_api_ysepay {

	public function getPlatformCode() {
		return YSEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ysepay';
	}

	protected function getMethod() {
		return parent::PAY_METHOD_DIRECTPAY;
	}

	public function getBankType($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

}
