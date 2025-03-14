<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dinpay.php';

/**
 * DINPAY 智付
 * http://www.dinpay.com
 *
 * 	DINPAY_WEIXIN_PAYMENT_API, ID: 243
 *
 * Required Fields:
 *
 * * URL
 * * Secret - secret key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://api.dinpay.com/gateway/api/scanpay
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
class Payment_api_dinpay_weixin extends Abstract_payment_api_dinpay {
	# Implementation of abstract functions
	public function getPlatformCode() {
		return DINPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dinpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		## WeChatPay of Dinpay. Additional logic: different params, and we need to submit to server
		## before returning user the QRCode

		# Ref: WeChat Payment API of Dinpay.pdf, section 2.1.2
		$params['service_type'] = 'weixin_scan';

		$params['client_ip'] = $this->CI->utils->getIP();
		//$params['client_ip'] = '114.32.45.138';
		# Following parameters should not appear in wechat payment mode
		unset($params['redo_flag']);
		unset($params['input_charset']);
		unset($params['return_url']);
		unset($params['bank_code']);		
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}
}

////END OF FILE//////////////////
