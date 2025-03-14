<?php
require_once dirname(__FILE__) . '/abstract_payment_api_unp.php';

/**
 *
 * UNP UNP支付-网银
 * http://wiki.unpayonline.com:8800/doku.php?id=api_for_unp
 *
 * UNP_PAYMENT_API, ID: 271
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://center.qpay888.com/Bank
 * * Extra Info
 * > {
 * >	"unp_partner" : "## Partner ID ##",
 * >	"bank_list" : {
 * >		"962" : "_json:{\"1\": \"CTTIC\", \"2\": \"中信银行\"}",
 * >		"963" : "_json:{\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >		"964" : "_json:{\"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * >		"965" : "_json:{\"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * >		"967" : "_json:{\"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * >		"970" : "_json:{\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >		"971" : "_json:{\"1\": \"PSBC\", \"2\": \"邮政储蓄\"}",
 * >		"972" : "_json:{\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * >		"976" : "_json:{\"1\": \"SRCB\", \"2\": \"上海农村商业银行\"}",
 * >		"977" : "_json:{\"1\": \"SPDB\", \"2\": \"浦东发展银行\"}",
 * >		"978" : "_json:{\"1\": \"PAB\", \"2\": \"平安银行\"}",
 * >		"979" : "_json:{\"1\": \"NJCB\", \"2\": \"南京银行\"}",
 * >		"980" : "_json:{\"1\": \"CMBC\", \"2\": \"民生银行\"}",
 * >		"981" : "_json:{\"1\": \"BOCO\", \"2\": \"交通银行\"}",
 * >		"983" : "_json:{\"1\": \"HCCB\", \"2\": \"杭州银行\"}",
 * >		"985" : "_json:{\"1\": \"GDB\", \"2\": \"广东发展银行\"}",
 * >		"986" : "_json:{\"1\": \"CEB\", \"2\": \"光大银行\"}",
 * >		"987" : "_json:{\"1\": \"BEA\", \"2\": \"东亚银行\"}",
 * >		"989" : "_json:{\"1\": \"BCCB\", \"2\": \"北京银行\"}",
 * >	}
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_unp extends Abstract_payment_api_unp {

	public function getPlatformCode() {
		return UNP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'unp';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['tyid'] = self::PAYMENT_TYPE_BANK ;
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
