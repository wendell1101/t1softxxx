<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xxpay.php';

/**
 * xxpay
 *
 *
 * XXPAY_PAYMENT_API, ID: 5896
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
class Payment_api_xxpay extends Abstract_payment_api_xxpay {

	public function getPlatformCode() {
		return XXPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xxpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_bankcode'] = self::PAYTYPE_ONLINEBANK;
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
