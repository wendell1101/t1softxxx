<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lfbpay.php';

/**
 *
 * * LFBPAY_PAYMENT_API, ID: 388
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: lfbpay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lfbpay extends Abstract_payment_api_lfbpay {

	public function getPlatformCode() {
		return LFBPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lfbpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['service'] = self::SERVICE_B2C;
		$params['bankId'] = $bank;
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
