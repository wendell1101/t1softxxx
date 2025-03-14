<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zbpay.php';

/**
 * ZBPAY 众宝支付
 * https://merchant.zbpay365.com/
 *
 * ZBPAY_PAYMENT_API, ID: 221
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Secret Key
 *
 * Field Values:
 *
 * * URL: https://gateway.zbpay365.com/GateWay/Pay
 * * Extra Info:
 * > {
 * > 	"bank_list" : {
 * > 		"962" : "_json: {\"1\" : \"China CITIC Bank\", \"2\" : \"中信银行\" }",
 * > 		"963" : "_json: {\"1\" : \"Bank of China\", \"2\" : \"中国银行\" }",
 * > 		"964" : "_json: {\"1\" : \"Agricultural Bank of China\", \"2\" : \"中国农业银行\" }",
 * > 		"965" : "_json: {\"1\" : \"China Construction Bank\", \"2\" : \"中国建设银行\" }",
 * > 		"967" : "_json: {\"1\" : \"Industrial and Commercial Bank of China\", \"2\" : \"中国工商银行\" }",
 * > 		"970" : "_json: {\"1\" : \"China Merchants Bank\", \"2\" : \"招商银行\" }",
 * > 		"971" : "_json: {\"1\" : \"Postal Savings Bank of China\", \"2\" : \"邮政储蓄\" }",
 * > 		"972" : "_json: {\"1\" : \"Industrial Bank Co.\", \"2\" : \"兴业银行\" }",
 * > 		"977" : "_json: {\"1\" : \"Shanghai Pudong Development Bank\", \"2\" : \"浦东发展银行\" }",
 * > 		"979" : "_json: {\"1\" : \"Bank Of Nanjing\", \"2\" : \"南京银行\" }",
 * > 		"980" : "_json: {\"1\" : \"China Minsheng Bank\", \"2\" : \"民生银行\" }",
 * > 		"981" : "_json: {\"1\" : \"Bank of Communications\", \"2\" : \"交通银行\" }",
 * > 		"983" : "_json: {\"1\" : \"Bank of Hangzhou\", \"2\" : \"杭州银行\" }",
 * > 		"985" : "_json: {\"1\" : \"China Guangfa Bank\", \"2\" : \"广东发展银行\" }",
 * > 		"986" : "_json: {\"1\" : \"China Everbright Bank\", \"2\" : \"光大银行\" }",
 * > 		"989" : "_json: {\"1\" : \"Bank of Beijing\", \"2\" : \"北京银行\" }",
 * > 		"990" : "_json: {\"1\" : \"Ping An Bank\", \"2\" : \"平安银行\" }",
 * > 		"991" : "_json: {\"1\" : \"Hua Xia Bank\", \"2\" : \"华夏银行\" }",
 * > 		"992" : "_json: {\"1\" : \"Bank of Shanghai\", \"2\" : \"上海银行\" }"
 * >    }
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zbpay extends Abstract_payment_api_zbpay {

	public function getPlatformCode() {
		return ZBPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zbpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['paytype'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

    protected function processPaymentUrlForm($params) {
        if($this->CI->utils->is_mobile()) {
            return $this->processPaymentUrlFormPost($params);
        }else{
            return $this->processPaymentUrlFormPost($params);
        }
    }

}
