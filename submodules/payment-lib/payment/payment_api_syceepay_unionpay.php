<?php
require_once dirname(__FILE__) . '/abstract_payment_api_syceepay.php';

/**
 *
 *
 * SYCEEPAY_YSF_PAYMENT_API, ID: 5453
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://syceepay.com/api/order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_syceepay_unionpay extends Abstract_payment_api_syceepay {

	public function getPlatformCode() {
		return SYCEEPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'syceepay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['method'] = self::METHOD_UNIONPAY;
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
