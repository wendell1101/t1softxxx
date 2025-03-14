<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypay.php';
/** 
 *
 * EASYPAY 
 * 
 * 
 * * EASYPAY_PAYMENT_API, ID: 651
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
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_easypay extends Abstract_payment_api_easypay {

	public function getPlatformCode() {
		return EASYPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'easypay';
	}

   

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['v_bankno'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['v_app'] = "web";
		$params['v_pagecode'] =  self::DEFAULTNANK_BANK;
		
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}
}
