<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tubeipay.php';

/**
 * TUBEIPAY 途贝支付 - QQ H5
 * 
 *
 * TUBEIPAY_QQPAY_H5_PAYMENT_API, ID: 423
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.tubeipay.com/v1/pay/unifiedorder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tubeipay_qqpay_h5 extends Abstract_payment_api_tubeipay {

	public function getPlatformCode() {
		return TUBEIPAY_QQPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tubeipay_qqpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['trade_type'] = self::PAYTYPE_QQPAY_H5;
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
