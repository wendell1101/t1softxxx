<?php
require_once dirname(__FILE__) . '/abstract_payment_api_999pay.php';

/** 
 *
 * 汇天付
 * 
 * 
 * * _999PAY_PAYMENT_API, ID: 447
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.999pays.com/Pay/KDBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_999pay extends Abstract_payment_api_999pay {

	public function getPlatformCode() {
		return _999PAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '999pay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['P_ChannelID'] = self::P_CHANNEL_BANK;
		$params['P_Description'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
