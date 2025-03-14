<?php
require_once dirname(__FILE__) . '/abstract_payment_api_miaofu.php';

/**
 *
 * Miaofu 秒付
 *
 * MIAOFU_PAYMENT_API, ID: 127
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://pay.miaofupay.com/gateway
 * * Account: ## Merchant ID ##
 * * Key: ## MD5 Key ##
 * * Extra Info:
 * > {
 * >  	bank_list: {
 * >  		"ABC" : "_json:{\"1\" : \"ABC\", \"2\" : \"中国农业银行\"}",
 * >  		"BOC" : "_json:{\"1\" : \"BOC\", \"2\" : \"中国银行\"}",
 * >  		"BOCOM" : "_json:{\"1\" : \"BOCOM\", \"2\" : \"交通银行\"}",
 * >  		"CCB" : "_json:{\"1\" : \"CCB\", \"2\" : \"中国建设银行\"}",
 * >  		"ICBC" : "_json:{\"1\" : \"ICBC\", \"2\" : \"中国工商银行\"}",
 * >  		"PSBC" : "_json:{\"1\" : \"PSBC\", \"2\" : \"中国邮政储蓄银行\"}",
 * >  		"CMBC" : "_json:{\"1\" : \"CMBC\", \"2\" : \"招商银行\"}",
 * >  		"SPDB" : "_json:{\"1\" : \"SPDB\", \"2\" : \"浦发银行\"}",
 * >  		"CEBBANK" : "_json:{\"1\" : \"CEBBANK\", \"2\" : \"中国光大银行\"}",
 * >  		"ECITIC" : "_json:{\"1\" : \"ECITIC\", \"2\" : \"中信银行\"}",
 * >  		"PINGAN" : "_json:{\"1\" : \"PINGAN\", \"2\" : \"平安银行\"}",
 * >  		"CMBCS" : "_json:{\"1\" : \"CMBCS\", \"2\" : \"中国民生银行\"}",
 * >  		"HXB" : "_json:{\"1\" : \"HXB\", \"2\" : \"华夏银行\"}",
 * >  		"CGB" : "_json:{\"1\" : \"CGB\", \"2\" : \"广发银行\"}",
 * >  		"BCCB" : "_json:{\"1\" : \"BCCB\", \"2\" : \"北京银行\"}",
 * >  		"BOS" : "_json:{\"1\" : \"BOS\", \"2\" : \"上海银行\"}",
 * >  		"BRCB" : "_json:{\"1\" : \"BRCB\", \"2\" : \"北京农商银行\"}",
 * >  		"CIB" : "_json:{\"1\" : \"CIB\", \"2\" : \"兴业银行\"}",
 * >  		"SRCB" : "_json:{\"1\" : \"SRCB\", \"2\" : \"上海农商银行\"}"
 * >  	}
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_miaofu extends Abstract_payment_api_miaofu {

	public function getPlatformCode() {
		return MIAOFU_PAYMENT_API;
	}

	public function getPrefix() {
		return 'miaofu';
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
