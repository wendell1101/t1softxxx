<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dinpay.php';

/**
 * DINPAY 智付
 * http://www.dinpay.com
 *
 * DINPAY_PAYMENT_API, ID: 27
 *
 * Required Fields:
 *
 * * URL
 * * Secret - secret key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://pay.dinpay.com/gateway?input_charset=UTF-8
 * * Extra Info:
 * > {
 * >     "dinpay_merchant_code": "##merchant code##",
 * >     "dinpay_service_type": "direct_pay",
 * >     "dinpay_sign_type": "RSA-S",
 * >     "dinpay_interface_version": "V3.0",
 * >     "dinpay_input_charset": "UTF-8",
 * >     "dinpay_merchant_private_key_path": "/path_to/private_key.pem",
 * >     "dinpay_api_public_key_path": "/path_to/public_key.pem",
 * >     "dinpay_weixin_url": "https://api.dinpay.com/gateway/api/weixin"
 * > }
 * >
 * > for MD5
 * > use secret field
 * >
 * > {
 * >     "dinpay_merchant_code": "##merchant code##",
 * >     "dinpay_service_type": "direct_pay",
 * >     "dinpay_sign_type": "MD5",
 * >     "dinpay_interface_version": "V3.0",
 * >     "dinpay_input_charset": "UTF-8",
 * >     "dinpay_merchant_private_key_path": "/path_to/private_key.pem",
 * >     "dinpay_api_public_key_path": "/path_to/public_key.pem",
 * >     "dinpay_weixin_url": "https://api.dinpay.com/gateway/api/weixin"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dinpay extends Abstract_payment_api_dinpay {
	# Implementation of abstract functions
	public function getPlatformCode() {
		return DINPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dinpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {
		$tree = ['cashier'=>[]];

		return array(
			// array('name' => 'bank_list', 'type' => 'bank_list', 'label_lang' => 'cashier.81',
			// 	'external_system_id' => $this->getPlatformCode(),
			// 	'bank_tree' => $tree, 'bank_list' => ['cashier'=>'收银台']),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

}

////END OF FILE//////////////////
