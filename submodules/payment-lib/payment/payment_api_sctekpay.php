<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sctekpay.php';

/** 
 *
 * 盛灿
 * 
 * 
 * * SCTEKPAY_PAYMENT_API, ID: 462
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * LIVE-URL: https://rpi.speedpos.cn/ecurrencypay/pay
 * * TEST-URL: http://rpi.snsshop.net/ecurrencypay/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sctekpay extends Abstract_payment_api_sctekpay {

	public function getPlatformCode() {
		return SCTEKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sctekpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['bank_name'] = $this->getBankName($bank);
	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}
}
