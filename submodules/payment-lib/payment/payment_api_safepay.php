<?php
require_once dirname(__FILE__) . '/abstract_payment_api_safepay.php';

/**
 *
 * * SAFEPAY_PAYMENT_API, ID: 362
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.safepaychannel.com/diy/demo/message.jsp
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_safepay extends Abstract_payment_api_safepay {

	public function getPlatformCode() {
		return SAFEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'safepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['bank'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
