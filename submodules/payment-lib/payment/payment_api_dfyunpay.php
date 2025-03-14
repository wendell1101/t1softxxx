<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dfyunpay.php';
/**
 *
 * * DFYUNPAY_PAYMENT_API, ID: 500
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.adsstore.cn//Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dfyunpay extends Abstract_payment_api_dfyunpay {

	public function getPlatformCode() {
		return DFYUNPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dfyunpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['pay_bankcode'] = $bank;
		$params["tongdao"] = self::TONGDAO_BANK;		
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}