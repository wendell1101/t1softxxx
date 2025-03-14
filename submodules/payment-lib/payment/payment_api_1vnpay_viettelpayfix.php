<?php
require_once dirname(__FILE__) . '/abstract_payment_api_1vnpay.php';

/**
 *
 * * _1VNPAY_VIETTELPAYFIX_PAYMENT_API, ID: 6022
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.1vnpay.org/api/v1/fundtransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_1vnpay_viettelpayfix extends Abstract_payment_api_1vnpay {

	public function getPlatformCode() {
		return _1VNPAY_VIETTELPAYFIX_PAYMENT_API;
	}

	public function getPrefix() {
		return '1VNPAY_VIETTELPAYFIX';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channel'] = self::CHANNEL_VIETTELPAYFIX;
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
