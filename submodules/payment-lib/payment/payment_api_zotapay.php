<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zotapay.php';

/**
 *
 * * ZOTAPAY_PAYMENT_API, ID: 367
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mg-api.zotapay.com/api/v1/deposit/request/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zotapay extends Abstract_payment_api_zotapay {

	public function getPlatformCode() {
		return ZOTAPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zotapay';
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
		return $this->processPaymentUrl($params);
	}
}
