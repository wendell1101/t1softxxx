<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fktpay.php';
/**
 * FKTPAY_PAYMENT_API, ID:579
 *
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_fktpay extends Abstract_payment_api_fktpay {

	public function getPlatformCode() {
		return FKTPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'fktpay';
	}

   

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
        
		$params['pay_type'] = self::PAYTYPE_BANK;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
