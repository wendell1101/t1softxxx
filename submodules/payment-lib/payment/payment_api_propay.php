<?php
require_once dirname(__FILE__) . '/abstract_payment_api_propay.php';

/**
 * propay
 *
 *
 * PROPAY_PAYMENT_API, ID: 6069
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payv2.surperpay.com/pay/gatewayPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_propay extends Abstract_payment_api_propay {

	public function getPlatformCode() {
		return PROPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'propay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payMethod'] = self::PAYTYPE_ONLINEBANK;
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
