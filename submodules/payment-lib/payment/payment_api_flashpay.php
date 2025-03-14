<?php
require_once dirname(__FILE__) . '/abstract_payment_api_flashpay.php';

/**
 * FlashPay 闪付/随意付
 *
 * * FLASHPAY_PAYMENT_API, ID: 108
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://gateway.easyipay.com/interface/AutoBank/index.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra Info
 * > {
 * >	"bank_list" : {
 * >		"962" : "_json:{\"1\": \"CTTIC\", \"2\": \"中信银行\"}",
 * >		"963" : "_json:{\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >		"964" : "_json:{\"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * >		"965" : "_json:{\"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * >		"967" : "_json:{\"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * >		"970" : "_json:{\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >		"971" : "_json:{\"1\": \"PSBC\", \"2\": \"邮政储蓄\"}",
 * >		"972" : "_json:{\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * >		"977" : "_json:{\"1\": \"SPDB\", \"2\": \"浦东发展银行\"}",
 * >		"978" : "_json:{\"1\": \"PAB\", \"2\": \"平安银行\"}",
 * >		"980" : "_json:{\"1\": \"CMBC\", \"2\": \"民生银行\"}",
 * >		"981" : "_json:{\"1\": \"BOCO\", \"2\": \"交通银行\"}",
 * >        "982" : "_json:{\"1\": \"HXBC\", \"2\": \"华夏银行\"}",
 * >		"985" : "_json:{\"1\": \"GDB\", \"2\": \"广东发展银行\"}",
 * >		"986" : "_json:{\"1\": \"CEB\", \"2\": \"光大银行\"}"
 * >	}
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_flashpay extends Abstract_payment_api_flashpay {

	public function getPlatformCode() {
		return FLASHPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'flashpay';
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
