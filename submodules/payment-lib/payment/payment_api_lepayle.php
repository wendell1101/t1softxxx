<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lepayle.php';

/**
 * 新乐付 LEPAYLE
 * https://cms.lepayle.com/
 *
 * LEPAYLE_WEIXIN_PAYMENT_API, ID: 254
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://api.lepayle.com/gateway/api/scanpay
 * * Extra Info:
 * > {
 * > 	"lepayle_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"lepayle_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_lepayle extends Abstract_payment_api_lepayle {

	public function getPlatformCode() {
		return LEPAYLE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lepayle_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		unset($params['wx_pay_type']);
		unset($params['subject']);
		unset($params['sub_body']);

		$params['service'] = 'gateway_pay';
		$params['tran_ip'] = $this->getClientIp();
		//$params['tran_ip'] = '220.135.118.23';
		$params['good_name'] = 'Deposit';
		$params['goods_detail'] = 'Deposit';
		$params['bank_code'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
