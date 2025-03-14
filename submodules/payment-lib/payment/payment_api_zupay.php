<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zupay.php';
/** 
 *
 * ZUPAY 
 * 
 * 
 * * ZUPAY_PAYMENT_API, ID: 707
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.zupay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zupay extends Abstract_payment_api_zupay {

	public function getPlatformCode() {
		return ZUPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zupay';
	}

   

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
                $params['pay_bankcode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
