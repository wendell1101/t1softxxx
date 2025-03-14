<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zhihpay.php';

/**
 * ZHIHPAY 智汇付
 * http://www.zhihpay.com
 *
 * ZHIHPAY_PAYMENT_API, ID: 175
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://pay.zhihpay.com/gateway?input_charset=UTF-8
 * * Extra Info:
 * > {
 * > 	"zhihpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"zhihpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zhihpay extends Abstract_payment_api_zhihpay {

	public function getPlatformCode() {
		return ZHIHPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zhihpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service_type'] = 'direct_pay';
		$params['pay_type'] = 'b2c';
		$params['interface_version'] = 'V3.0';

		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		unset($params['bank_code']);
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
