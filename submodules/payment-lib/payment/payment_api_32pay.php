<?php
require_once dirname(__FILE__) . '/abstract_payment_api_32pay.php';
/**
 * 32PAY 32支付
 *
 * * _32PAY_PAYMENT_API, ID: 509
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.32pay.com/Pay/KDBank.aspx
 * * Account: ## merchant ID ##
 * * Key: ## secret key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_32pay extends Abstract_payment_api_32pay {

	public function getPlatformCode() {
		return _32PAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '32pay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['P_ChannelId'] = self::P_CHANNEL_BANK;
		$params['P_Description'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
