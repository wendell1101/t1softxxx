<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fxmb.php';

/**
 * FXMB
 *
 * * FXMB_PAYMENT_API, ID: 5819
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.fxmb8.com/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fxmb extends Abstract_payment_api_fxmb {

	public function getPlatformCode() {
		return FXMB_PAYMENT_API;
	}

	public function getPrefix() {
		return 'fxmb';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormRedirect($params);
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
