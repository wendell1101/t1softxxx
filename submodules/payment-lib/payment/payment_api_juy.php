<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juy.php';

/**
 *
 * juy 聚源-网银
 *
 * JUY_PAYMENT_API, ID: 103
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: http://pay.juypay.com/PayBank.aspx
 * * Extra Info:
 * > {
 * >    "juy_partner" : "## Partner ID ##",
 * >    "bank_list" : {
 * >        "ICBC" : "_json:{\"1\": \"ICBC\", \"2\": \"工商银行\"}",
 * >        "ABC" : "_json:{\"1\": \"ABC\", \"2\": \"农业银行\"}",
 * >        "CCB" : "_json:{\"1\": \"CCB\", \"2\": \"建设银行\"}",
 * >        "BOC" : "_json:{\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >        "CMB" : "_json:{\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >        "BCCB" : "_json:{\"1\": \"BCCB\", \"2\": \"北京银行\"}",
 * >        "BOCO" : "_json:{\"1\": \"BOCO\", \"2\": \"交通银行\"}",
 * >        "CIB" : "_json:{\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * >        "NJCB" : "_json:{\"1\": \"NJCB\", \"2\": \"南京银行\"}",
 * >        "CMBC" : "_json:{\"1\": \"CMBC\", \"2\": \"民生银行\"}",
 * >        "CEB" : "_json:{\"1\": \"CEB\", \"2\": \"光大银行\"}",
 * >        "PINGANBANK" : "_json:{\"1\": \"PINGANBANK\", \"2\": \"平安银行\"}",
 * >        "CBHB" : "_json:{\"1\": \"CBHB\", \"2\": \"渤海银行\"}",
 * >        "HKBEA" : "_json:{\"1\": \"HKBEA\", \"2\": \"东亚银行\"}",
 * >        "NBCB" : "_json:{\"1\": \"NBCB\", \"2\": \"宁波银行\"}",
 * >        "CTTIC" : "_json:{\"1\": \"CTTIC\", \"2\": \"中信银行\"}",
 * >        "GDB" : "_json:{\"1\": \"GDB\", \"2\": \"广发银行\"}",
 * >        "SHB" : "_json:{\"1\": \"SHB\", \"2\": \"上海银行\"}",
 * >        "SPDB" : "_json:{\"1\": \"SPDB\", \"2\": \"上海浦东发展银行\"}",
 * >        "PSBS" : "_json:{\"1\": \"PSBS\", \"2\": \"中国邮政\"}",
 * >        "HXB" : "_json:{\"1\": \"HXB\", \"2\": \"华夏银行\"}",
 * >        "BJRCB" : "_json:{\"1\": \"BJRCB\", \"2\": \"北京农村商业银行\"}",
 * >        "SRCB" : "_json:{\"1\": \"SRCB\", \"2\": \"上海农商银行\"}",
 * >        "SDB" : "_json:{\"1\": \"SDB\", \"2\": \"深圳发展银行\"}",
 * >        "CZB" : "_json:{\"1\": \"CZB\", \"2\": \"浙江稠州商业银行\"}"
 * >     }
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juy extends Abstract_payment_api_juy {

	public function getPlatformCode() {
		return JUY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juy';
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
