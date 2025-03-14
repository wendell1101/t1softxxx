<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cssf.php';

/**
 *
 * * CSSF_PAYMENT_API, ID: 340
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://116.62.163.144:8081/openapi/pay/cardpay/cardpayapply
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cssf extends Abstract_payment_api_cssf {

	public function getPlatformCode() {
		return CSSF_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cssf';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['bankCode'] = $bank;
		//$params['bankCode'] = '6666';	//for test environment
		$params['userType'] = '1';
		$params['cardType'] = '1';
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
