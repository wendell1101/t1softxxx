<?php
require_once dirname(__FILE__) . '/payment_api_dpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_2_PAYMENT_API, ID: 5123
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.273787.cn/api/cashierpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_2 extends Payment_api_dpay {

	public function getPlatformCode() {
		return DPAY_2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dpay_2';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['bankcode'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
