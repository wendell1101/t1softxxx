<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hbepay.php';

/** 
 *
 * HBEPAY 汇宝
 * 
 * 
 * * 'HBEPAY_PAYMENT_API', ID 5306
 *
 * Required Fields:
 * * URL:'https://api.hbepay.com/api/gateway/receiveOrder'
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hbepay extends Abstract_payment_api_hbepay {

	public function getPlatformCode() {
		return HBEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hbepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_sn'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
        $params['service'] = 'S1004';
		$params['sign_type'] = 'MD5';
        $params['subject'] = 'Deposit';
        $params['sign'] = $this->MD5sign($params);
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormPost($params);
	}

}
