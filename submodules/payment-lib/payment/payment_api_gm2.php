<?php
require_once dirname(__FILE__) . '/payment_api_gm.php';

/**
 * GMStone
 * http://www.gmstoneft.com
 *
 * * GM2_PAYMENT_API, ID: 180
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.master-egg.cn/GateWay/ReceiveBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info:
 * > {
 * >    "bank_list" : {
 * > 		"ABC" : "_json: {\"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * > 		"BJBANK" : "_json: {\"1\": \"BJBANK\", \"2\": \"北京银行\"}",
 * > 		"BJRCB" : "_json: {\"1\": \"BJRCB\", \"2\": \"北京农商银行\"}",
 * > 		"BOC" : "_json: {\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * > 		"CEB" : "_json: {\"1\": \"CEB\", \"2\": \"中国光大银行\"}",
 * > 		"CIB" : "_json: {\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * > 		"CITIC" : "_json: {\"1\": \"CITIC\", \"2\": \"中信银行\"}",
 * > 		"CMBC" : "_json: {\"1\": \"CMBC\", \"2\": \"中国民生银行\"}",
 * > 		"ICBC" : "_json: {\"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * > 		"NBBANK" : "_json: {\"1\": \"NBBANK\", \"2\": \"宁波银行\"}",
 * > 		"SPABANK" : "_json: {\"1\": \"SPABANK\", \"2\": \"平安银行\"}",
 * > 		"HXBANK" : "_json: {\"1\": \"HXBANK\", \"2\": \"华夏银行\"}",
 * > 		"SPDB" : "_json: {\"1\": \"SPDB\", \"2\": \"浦发银行\"}",
 * > 		"PSBC" : "_json: {\"1\": \"PSBC\", \"2\": \"中国邮政储蓄银行\"}",
 * > 		"HZCB" : "_json: {\"1\": \"HZCB\", \"2\": \"杭州银行\"}",
 * > 		"NJCB" : "_json: {\"1\": \"NJCB\", \"2\": \"南京银行\"}",
 * > 		"COMM" : "_json: {\"1\": \"COMM\", \"2\": \"交通银行\"}",
 * > 		"CMB" : "_json: {\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * > 		"CCB" : "_json: {\"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * > 		"GDB" : "_json: {\"1\": \"GDB\", \"2\": \"广发银行\"}"
 * >    }
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gm2 extends Payment_api_gm {

	public function getPlatformCode() {
		return GM2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gm2';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}
}
