<?php
require_once dirname(__FILE__) . '/abstract_payment_api_okfpay.php';

/**
 *
 * OKFPay OK付
 * http://www.okfpay.com
 *
 * OKFPAY_PAYMENT_API, ID: 111
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://gateway.okfpay.com/Gate/payindex.aspx
 * * Extra Info
 * > {
 * >	"okfpay_partner" : "## Partner ID ##",
 * >	"bank_list" : {
 * >		"ICBC" : "_json: { \"1\": \"ICBC\", \"2\": \"工商银行\" }",
 * >		"CMB" : "_json: { \"1\": \"CMB\", \"2\": \"招商银行\" }",
 * >		"CCB" : "_json: { \"1\": \"CCB\", \"2\": \"建设银行\" }",
 * >		"BOC" : "_json: { \"1\": \"BOC\", \"2\": \"中国银行\" }",
 * >		"ABC" : "_json: { \"1\": \"ABC\", \"2\": \"农业银行\" }",
 * >		"BOCM" : "_json: { \"1\": \"BOCM\", \"2\": \"交通银行\" }",
 * >		"SPDB" : "_json: { \"1\": \"SPDB\", \"2\": \"浦发银行\" }",
 * >		"CGB" : "_json: { \"1\": \"CGB\", \"2\": \"广发银行\" }",
 * >		"CTITC" : "_json: { \"1\": \"CTITC\", \"2\": \"中信银行\" }",
 * >		"CEB" : "_json: { \"1\": \"CEB\", \"2\": \"光大银行\" }",
 * >		"CIB" : "_json: { \"1\": \"CIB\", \"2\": \"兴业银行\" }",
 * >		"SDB" : "_json: { \"1\": \"SDB\", \"2\": \"平安银行\" }",
 * >		"CMBC" : "_json: { \"1\": \"CMBC\", \"2\": \"民生银行\" }",
 * >		"HXB" : "_json: { \"1\": \"HXB\", \"2\": \"华夏银行\" }",
 * >		"PSBC" : "_json: { \"1\": \"PSBC\", \"2\": \"邮储银行\" }",
 * >		"BCCB" : "_json: { \"1\": \"BCCB\", \"2\": \"北京银行\" }",
 * >		"SHBANK" : "_json: { \"1\": \"SHBANK\", \"2\": \"上海银行\" }",
 * >		"BOHAI" : "_json: { \"1\": \"BOHAI\", \"2\": \"渤海银行\" }",
 * >		"SHNS" : "_json: { \"1\": \"SHNS\", \"2\": \"上海农商\" }",
 * >		"UNION" : "_json: { \"1\": \"UNION\", \"2\": \"银联支付\" }"
 * >	}
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_okfpay extends Abstract_payment_api_okfpay {

	public function getPlatformCode() {
		return OKFPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'okfpay';
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
