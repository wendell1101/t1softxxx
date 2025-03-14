<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypay.php';
/**
 * EASYPAY 
 * 
 *
 * easypay_WEIXIN_PAYMENT_API, ID: 657
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.easypay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_easypay_weixin extends Abstract_payment_api_easypay {

	public function getPlatformCode() {
		return EASYPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'easypay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			$params['v_app'] = "app";
			$params['v_pagecode'] = self::DEFAULTNANK_WEIXIN_H5;
		}
		else {
			$params['v_app'] = "web";
			$params['v_pagecode'] = self::DEFAULTNANK_WEIXIN;
		}	
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
	
		//return $this->processPaymentUrlFormQRCode($params);
		
		return $this->processPaymentUrlFormQRCode($params);
		
	}

}
