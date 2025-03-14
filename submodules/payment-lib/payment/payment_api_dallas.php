<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dallas.php';

/**
 * dallas
 *
 *
 * DALLAS_PAYMENT_API, ID: 6133
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
class Payment_api_dallas extends Abstract_payment_api_dallas {

	public function getPlatformCode() {
		return DALLAS_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dallas';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
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
