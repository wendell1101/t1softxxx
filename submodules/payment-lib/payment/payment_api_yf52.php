<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yf52.php';

/**
 *
 * * YF52_PAYMENT_API, ID: 340
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://way.yf52.com/api/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yf52 extends Abstract_payment_api_yf52 {

	public function getPlatformCode() {
		return YF52_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yf52';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['p_type'] = self::PAYTYPE_BANK;
		$params['p_bank'] = $bank;
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
