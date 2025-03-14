<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huabeitong.php';

/**
 * HUABEITONG
 *
 *
 * HUABEITONG_WECHATSCAN_PAYMENT_API, ID: 5958
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.huabeitong.net/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huabeitong_wechatscan extends Abstract_payment_api_huabeitong {

	public function getPlatformCode() {
		return HUABEITONG_WECHATSCAN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huabeitong_wechatscan';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['p4_paytype'] = self::PAYMENT_TYPE_WECHATSCAN;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
