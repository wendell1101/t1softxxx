<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lanyepay.php';

/**
 * LANYEPAY 蓝叶支付
 *
 * * LANYEPAY_PAYMENT_API, ID: 412
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://openapi.lanyepay.cn/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lanyepay extends Abstract_payment_api_lanyepay {

	public function getPlatformCode() {
		return LANYEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lanyepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['paytype'] = self::PAYTYPE_BANK;
		$params['bankcode'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
