<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bofubaopay.php';

/** 
 *
 * 博付宝
 * 
 * 
 * * BOFUBAOPAY_PAYMENT_API, ID: 458
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://bofubao.qingzhuzi.com/qingpay.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bofubaopay extends Abstract_payment_api_bofubaopay {

	public function getPlatformCode() {
		return BOFUBAOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bofubaopay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['bank'] = self::BANK_BANK;
	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}
}
