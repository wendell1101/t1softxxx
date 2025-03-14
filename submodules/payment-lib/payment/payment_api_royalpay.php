<?php
require_once dirname(__FILE__) . '/abstract_payment_api_royalpay.php';

/**
 *
 * * ROYALPAY_PAYMENT_API, ID: 6024
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.bee-earning.com/order/order/submit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_royalpay extends Abstract_payment_api_royalpay {//payment_api_royalpay

	public function getPlatformCode() {
		return ROYALPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'royalpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payType'] = (int)self::PAYTYPE_BANK;
	}	

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormRedirect($params);
	}
}
