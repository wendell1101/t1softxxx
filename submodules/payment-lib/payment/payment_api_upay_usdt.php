<?php
require_once dirname(__FILE__) . '/abstract_payment_api_upay.php';

/**
 * upay
 *
 * * UPAY_USDT_PAYMENT_API', ID 6182
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class payment_api_upay_usdt extends Abstract_payment_api_upay {

	public function getPlatformCode() {
		return UPAY_USDT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'upay_usdt';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['protocol'] = $this->getSystemInfo("protocol", self::PROTOCOL_TYPE_TRC);
	}

	protected function processPaymentUrlForm($params) {
		return $this->handlePaymentFormResponse($params);
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo()
	{
		return array(array('name' => 'deposit_amount', 'type' => 'integer_amount', 'label_lang' => 'cashier.09'));
	}
}
