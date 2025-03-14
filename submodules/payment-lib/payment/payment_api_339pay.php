<?php
require_once dirname(__FILE__) . '/abstract_payment_api_339pay.php';

/**
 * 339PAY叁叁玖 - 支付宝
 * http://www.sz339pay.com:9001/
 *
 * * 339PAY_PAYMENT_API, ID: 214
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
 * * URL: /gateway_init.action
 * * Extra Info:
 * > {
 * >	"bank_list" : {
 * >		"ICBC_NET_B2C" : "_json:{\"1\": \"ICBC_NET_B2C\", \"2\": \"工商银行\"}",
 * >		"CMBCHINA_NET_B2C" : "_json:{\"1\": \"CMBCHINA_NET_B2C\", \"2\": \"招商银行\"}",
 * >		"ABC_NET_B2C" : "_json:{\"1\": \"ABC_NET_B2C\", \"2\": \"中国农业银行\"}",
 * >		"CCB_NET_B2C" : "_json:{\"1\": \"CCB_NET_B2C\", \"2\": \"建设银行\"}",
 * >		"BCCB_NET_B2C" : "_json:{\"1\": \"BCCB_NET_B2C\", \"2\": \"北京银行\"}",
 * >		"BOCO_NET_B2C" : "_json:{\"1\": \"BOCO_NET_B2C\", \"2\": \"交通银行\"}",
 * >		"CIB_NET_B2C" : "_json:{\"1\": \"CIB_NET_B2C\", \"2\": \"兴业银行\"}",
 * >		"CMBC_NET_B2C" : "_json:{\"1\": \"CMBC_NET_B2C\", \"2\": \"中国民生银行\"}",
 * >		"CEB_NET_B2C" : "_json:{\"1\": \"CEB_NET_B2C\", \"2\": \"中国银行\"}",
 * >		"BOC_NET_B2C" : "_json:{\"1\": \"BOC_NET_B2C\", \"2\": \"平安银行\"}",
 * >		"PINGANBANK_NET_B2C" : "_json:{\"1\": \"PINGANBANK_NET_B2C\", \"2\": \"中信银行\"}",
 * >		"ECITIC_NET_B2C" : "_json:{\"1\": \"ECITIC_NET_B2C\", \"2\": \"深圳发展银行\"}",
 * >		"SDB_NET_B2C" : "_json:{\"1\": \"SDB_NET_B2C\", \"2\": \"广发银行\"}",
 * >		"CGB_NET_B2C" : "_json:{\"1\": \"CGB_NET_B2C\", \"2\": \"华夏银行\"}",
 * >		"SPDB_NET_B2C" : "_json:{\"1\": \"SPDB_NET_B2C\", \"2\": \"浦发银行\"}",
 * >		"POST_NET_B2C" : "_json:{\"1\": \"POST_NET_B2C\", \"2\": \"东亚银行\"}",
 * >		"HXB_NET_B2C" : "_json:{\"1\": \"POST_NET_B2C\", \"2\": \"微信扫码\"}"
 * >	}
 * > }
 *
 * @category Payment

 * @copyright 2013-2022 tot
 */
class Payment_api_339pay extends Abstract_payment_api_339pay {

	public function getPlatformCode() {
		return _339PAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '339pay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

	}	

	public function getBankCode($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}	

}
