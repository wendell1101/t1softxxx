<?php
require_once dirname(__FILE__) . '/abstract_payment_api_duoduopay.php';

/**
 *DUODUOPAY
 *  
 * http://merchant.duoduopayment.com 
 * DUODUOPAY_PAYMENT_API, ID: 315
 *
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_duoduopay extends Abstract_payment_api_duoduopay {

	public function getPlatformCode() {
		return DUODUOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'duoduopay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
 
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['BankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
