<?php
require_once dirname(__FILE__) . '/abstract_payment_api_haohaopay.php';
/** 
 *
 * HAOHAOPAY 
 * 
 * 
 * * HAOHAOPAY_PAYMENT_API, ID: 748
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.haohaopay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_haohaopay extends Abstract_payment_api_haohaopay {

	public function getPlatformCode() {
		return HAOHAOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'haohaopay';
	}

   

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
                $params['bank_id'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['payType'] = self::DEFAULTNANK_BANK;
		
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
