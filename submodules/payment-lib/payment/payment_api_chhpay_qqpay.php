<?php
require_once dirname(__FILE__) . '/abstract_payment_api_chhpay.php';

/**
 * CHHPAY 畅汇
 * https://t24o.cn/
 *
 * CHHPAY_QQ_PAYMENT_API, ID: 304
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://changcon.chhpay.com/controller.action
 * * Extra Info:
 * > {
 * > 	"chhpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"chhpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_chhpay_qqpay extends Abstract_payment_api_chhpay {

	public function getPlatformCode() {
		return CHHPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'chhpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			$params['pa_FrpId'] = self::PAYTYPE_QQPAY_WAP;
		}
		else {
			$params['pa_FrpId'] = self::PAYTYPE_QQPAY;
		}	
		//$params['ph_Ip'] = $this->getClientIp();
	}

	protected function processPaymentUrlForm($params) {
		if($this->CI->utils->is_mobile()) {
			return $this->processPaymentUrlFormPost($params);
		}
		else {
			return $this->processPaymentUrlFormQRCode($params);
		}	
		
		
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
