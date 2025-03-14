<?php
require_once dirname(__FILE__) . '/abstract_payment_api_antopay.php';

/**
 *
 * ANTOPAY 云安付-网银
 * https://pay.antopay.com/antopay.html
 *
 * ANTOPAY_PAYMENT_API, ID: 235
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://pay.antopay.com/antopay.html
 * * Extra Info
 * > {
 * >	"antopay_partner" : "## Partner ID ##"
 * >	"bank_list" : {
 * >		"MSJD" : "_json: { \"1\" : \"MSJD\", \"2\" : \"京东支付\" } ",
 * >		"ICBC" : "_json: { \"1\" : \"ICBC\", \"2\" : \"工商银行\" } ",
 * >		"CMB" : "_json: { \"1\" : \"CMB\", \"2\" : \"招商银行\" } ",
 * >		"ABC" : "_json: { \"1\" : \"ABC\", \"2\" : \"农业银行\" } ",
 * >		"CCB" : "_json: { \"1\" : \"CCB\", \"2\" : \"建设银行\" } ",
 * >		"BOC" : "_json: { \"1\" : \"BOC\", \"2\" : \"中国银行\" } ",
 * >		"BOCO" : "_json: { \"1\" : \"BOCO\", \"2\" : \"交通银行\" } ",
 * >		"CIB" : "_json: { \"1\" : \"CIB\", \"2\" : \"兴业银行\" } ",
 * >		"CMBC" : "_json: { \"1\" : \"CMBC\", \"2\" : \"民生银行\" } ",
 * >		"CEB" : "_json: { \"1\" : \"CEB\", \"2\" : \"光大银行\" } ",
 * >		"PINGANBANK" : "_json: { \"1\" : \"PINGANBANK\", \"2\" : \"平安银行\" } ",
 * >		"GDB" : "_json: { \"1\" : \"GDB\", \"2\" : \"广发银行\" } ",
 * >		"CTTIC" : "_json: { \"1\" : \"CTTIC\", \"2\" : \"中信银行\" } ",
 * >		"PSBS" : "_json: { \"1\" : \"PSBS\", \"2\" : \"中国邮政\" } ",
 * >		"BCCB" : "_json: { \"1\" : \"BCCB\", \"2\" : \"北京银行\" } "
 * >	}
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_antopay extends Abstract_payment_api_antopay {

	public function getPlatformCode() {
		return ANTOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'antopay';
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
