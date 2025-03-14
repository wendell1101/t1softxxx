<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tubeipay.php';

/**
 * LPAY 玖玖支付
 * 
 *
 * LPAY_QUICKPAY_PAYMENT_API, ID: 5395 
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://lpay.9cp7c.com/pay/gateway
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lpay_quickpay extends Abstract_payment_api_tubeipay {

	public function getPlatformCode() {
		return LPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lpay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service'] = self::PAYTYPE_QUICKPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

}
