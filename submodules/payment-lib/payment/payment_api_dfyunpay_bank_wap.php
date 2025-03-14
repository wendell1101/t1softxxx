<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dfyunpay.php';
/**
 *
 * * DFYUNPAY_ALIPAY_PAYMENT_API, ID: 501
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
 * @copyright 2017-2022 tot
 */
class Payment_api_dfyunpay_bank_wap extends Abstract_payment_api_dfyunpay {

	public function getPlatformCode() {
		return DFYUNPAY_BANK_WAP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dfyunpay_bank_wap';
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
		$params["tongdao"] = self::TONGDAO_BANK_WAP;	
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
