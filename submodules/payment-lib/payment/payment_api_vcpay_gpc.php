<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vcpay.php';

/**
 *
 *
 * VCPAY_GPC_PAYMENT_API, ID: 5255
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://xxxx.com/Home/Jiekou/getParameterFromOther
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vcpay_gpc extends Abstract_payment_api_vcpay {

	public function getPlatformCode() {
		return VCPAY_GPC_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vcpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['type'] = self::TYPE_BUY;
		$params['abbreviation'] = self::CRYPTO_GPC;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormUrl($params);
	}

}
