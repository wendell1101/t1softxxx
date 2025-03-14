<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huidpay.php';

/**
 *
 * * HUIDPAY_PAYMENT_API, ID: 334
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ebank.huihuidpay.com 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 * > {
 * >    "sellerEmail" : "## Seller email address, system will show you when the merchant opens ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huidpay extends Abstract_payment_api_huidpay {

	public function getPlatformCode() {
		return HUIDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huidpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['defaultbank'] = $bank;
	}
}
