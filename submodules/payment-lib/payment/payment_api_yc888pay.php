<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yc888pay.php';

/**
 *
 * yc888pay
 *
 *
 * * 'YC888PAY_PAYMENT_API', ID 6028
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://yc888pay.cc/Apipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yc888pay extends Abstract_payment_api_yc888pay {

	public function getPlatformCode() {
		return YC888PAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yc888pay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['paytype'] = self::PAYWAY_BANK;
		$params['bankname'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
