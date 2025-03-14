<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yeepay.php';

/**
 * yeepay 易宝支付 - 支付宝
 *
 * YEEPAY_ALIPAY_PAYMENT_API, ID: 212
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "phone" : "## recieve pay merchant phone ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yeepay_alipay extends Abstract_payment_api_yeepay {
	const SCAN_PAYMENT_ID_ALIPAY = '2';

	public function getPlatformCode() {
		return YEEPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yeepay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['paymentId'] = self::SCAN_PAYMENT_ID_ALIPAY;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlQRCode($params);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
