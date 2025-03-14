<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypay.php';
/**
 * EASYPAY 
 * 
 *
 * EASYPAY_QQPAY_PAYMENT_API, ID: 652
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
class Payment_api_easypay_qqpay extends Abstract_payment_api_easypay {

	public function getPlatformCode() {
		return EASYPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'easypay_qqpay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
			//if($this->CI->utils->is_mobile()) {
            //    $params['v_app'] = "app";
			//	$params['v_pagecode'] = self::DEFAULTNANK_QQPAY_H5;
			//	$params['v_bankno'] ="0000";
			//}
			//else {
				$params['v_app'] = "web";
				$params['v_pagecode'] = self::DEFAULTNANK_QQPAY;
				$params['v_bankno'] ="0000";
			//}	
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		//if($this->CI->utils->is_mobile()) {
		//	return $this->processPaymentUrlFormQRCode($params);
		//}else{
			return $this->processPaymentUrlFormQRCode($params);
		//}
	}

}
