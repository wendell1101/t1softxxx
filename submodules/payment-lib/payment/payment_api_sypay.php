<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sypay.php';

/**
 * SYPAY
 *
 *
 * SYPAY_PAYMENT_API, ID: 6075
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
class Payment_api_sypay extends Abstract_payment_api_sypay {

	public function getPlatformCode() {
		return SYPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sypay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['PayCode'] = self::PAYMENT_TYPE_PIX;
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
