<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lgvpay.php';

/**
 *
 * * LGVPAY_PAYMENT_API, ID: 436
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://lycs.uas-gw.info/dev/deposit/leying/forward
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lgvpay extends Abstract_payment_api_lgvpay {

	public function getPlatformCode() {
		return LGVPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lgvpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['gateway'] = self::GATEWAY_BANKS;
		$params['bank'] = $bank;
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlGetMethod($params);
	}
}
