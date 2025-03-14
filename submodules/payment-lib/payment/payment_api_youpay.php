<?php
require_once dirname(__FILE__) . '/abstract_payment_api_youpay.php';

/**
 * YOUPAY 友付 - 网银
 * 
 *
 * YOUPAY_PAYMENT_API, ID: 5337
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payv2.surperpay.com/pay/gatewayPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_youpay extends Abstract_payment_api_youpay {

	public function getPlatformCode() {
		return YOUPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'youpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['cardType'] ='0';
		$params['bankCode'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
