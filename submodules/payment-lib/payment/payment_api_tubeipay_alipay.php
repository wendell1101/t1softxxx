<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tubeipay.php';

/**
 * TUBEIPAY 途贝支付 - 支付宝
 * 
 *
 * TUBEIPAY_ALIPAY_PAYMENT_API, ID: 419
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
class Payment_api_tubeipay_alipay extends Abstract_payment_api_tubeipay {

	public function getPlatformCode() {
		return TUBEIPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tubeipay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service'] = self::PAYTYPE_ALIPAY;
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
