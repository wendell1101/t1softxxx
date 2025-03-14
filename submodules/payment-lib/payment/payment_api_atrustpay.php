<?php
require_once dirname(__FILE__) . '/abstract_payment_api_atrustpay.php';

/** 
 *
 * ATRUSTPAY 信付宝
 * 
 * 
 * * ATRUSTPAY_PAYMENT_API, ID: 477
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.atrustpay.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_atrustpay extends Abstract_payment_api_atrustpay {

	public function getPlatformCode() {
		return ATRUSTPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'atrustpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		
		$params['receivableType'] = 'D00';   //D+0、T+1、D+1
		$params['payMode'] = self::PAYMODE_BANK;
		$params['tranChannel'] = $bank;
		
	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}
}
