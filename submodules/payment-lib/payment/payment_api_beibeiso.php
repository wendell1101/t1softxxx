<?php
require_once dirname(__FILE__) . '/abstract_payment_api_beibeiso.php';
/** 
 *
 * 新贝富支付
 * 
 * 
 * * BEIBEISO_PAYMENT_API, ID: 550
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.beibeiso.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_beibeiso extends Abstract_payment_api_beibeiso {

	public function getPlatformCode() {
		return BEIBEISO_PAYMENT_API;
	}

	public function getPrefix() {
		return 'beibeiso';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['type'] = $bank;
		//$params['P_Description'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
