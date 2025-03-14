<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lyingpay.php';

/**
 * LYINGPAY 利盈支付 - 网银
 * 
 *
 * LYINGPAY_PAYMENT_API, ID: 464
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://103.78.122.231:8356/payapi.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lyingpay extends Abstract_payment_api_lyingpay {

	public function getPlatformCode() {
		return LYINGPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lyingpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['trade_type'] = self::TRADETYPE_BANK;
		$params['bank_id'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
