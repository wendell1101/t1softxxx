<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zsagepay.php';

/**
 * ZSAGEPAY 泽圣
 * http://payment.zsagepay.com/
 *
 * * ZSAGEPAY_PAYMENT_API, ID: 309
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://cashier.zzffgateway.com
 * * Extra Info:
 * > {
 * >	"bank_list" : {
 * >		"ICBC" : "_json: {\"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * >		"CMB" : "_json: {\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >		"ABC" : "_json: {\"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * >		"CCB" : "_json: {\"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * >		"BCCB" : "_json: {\"1\": \"BCCB\", \"2\": \"北京银行\"}",
 * >		"BCM" : "_json: {\"1\": \"BCM\", \"2\": \"交通银行\"}",
 * >		"CIB" : "_json: {\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * >		"CMBC" : "_json: {\"1\": \"CMBC\", \"2\": \"中国民生银行\"}",
 * >		"CEB" : "_json: {\"1\": \"CEB\", \"2\": \"光大银行\"}",
 * >		"BOC" : "_json: {\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >		"PAB" : "_json: {\"1\": \"PAB\", \"2\": \"平安银行\"}",
 * >		"CITIC" : "_json: {\"1\": \"CITIC\", \"2\": \"中信银行\"}",
 * >		"GDB" : "_json: {\"1\": \"GDB\", \"2\": \"广发银行\"}",
 * >		"PSBC" : "_json: {\"1\": \"PSBC\", \"2\": \"中国邮政储蓄银行\"}"		
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zsagepay extends Abstract_payment_api_zsagepay {

	public function getPlatformCode() {
		return ZSAGEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zsagepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		unset($params['amount']); //amount
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
