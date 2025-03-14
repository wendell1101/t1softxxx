<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lyingpay.php';

/**
 * YOUPAY 友付 - 银联扫码
 *
 *
 * YOUPAY_UNIONPAY_PAYMENT_API, ID: 5251
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.surperpay.com/pay/nativePay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_youpay_unionpay extends Abstract_payment_api_lyingpay {
	const SERVICE_UNIONPAY = 'pay.union.native';

	public function getPlatformCode() {
		return YOUPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'youpay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['mch_create_ip'] = $this->getClientIP();
		$params['service'] = self::SERVICE_UNIONPAY;
		unset($params['attach']);
		unset($params['bank_id']);
		unset($params['return_url']);
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlQRCode($params);
	}

}
