<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jieepay.php';

/**
 * JIEEPAY捷付通
 * http://www.jieepay.com
 *
 * * JIEEPAY_PAYMENT_API, ID: 136
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
 * * URL: http://cashier.chinapay360.com/payment/
 * * Extra Info:
 * > {
 * >	"bank_list" : {
 * >		"ABC" : "_json:{\"1\": \"ABC\", \"2\": \"农业银行\"}",
 * >		"ICBC" : "_json:{\"1\": \"ICBC\", \"2\": \"工商银行\"}",
 * >		"CCB" : "_json:{\"1\": \"CCB\", \"2\": \"建设银行\"}",
 * >		"BCOM" : "_json:{\"1\": \"BCOM\", \"2\": \"交通银行\"}",
 * >		"BOC" : "_json:{\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >		"CMB" : "_json:{\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >		"CMBC" : "_json:{\"1\": \"CMBC\", \"2\": \"民生银行\"}",
 * >		"CEBB" : "_json:{\"1\": \"CEBB\", \"2\": \"光大银行\"}",
 * >		"CIB" : "_json:{\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * >		"PSBC" : "_json:{\"1\": \"PSBC\", \"2\": \"中国邮政\"}",
 * >		"SPABANK" : "_json:{\"1\": \"SPABANK\", \"2\": \"平安银行\"}",
 * >		"ECITIC" : "_json:{\"1\": \"ECITIC\", \"2\": \"中信银行\"}",
 * >		"GDB" : "_json:{\"1\": \"GDB\", \"2\": \"广东发展银行\"}",
 * >		"HXB" : "_json:{\"1\": \"HXB\", \"2\": \"华夏银行\"}",
 * >		"SPDB" : "_json:{\"1\": \"SPDB\", \"2\": \"浦发银行\"}",
 * >		"BEA" : "_json:{\"1\": \"BEA\", \"2\": \"东亚银行\"}"
 * >	}
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jieepay extends Abstract_payment_api_jieepay {

	public function getPlatformCode() {
		return JIEEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jieepay';
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

}
