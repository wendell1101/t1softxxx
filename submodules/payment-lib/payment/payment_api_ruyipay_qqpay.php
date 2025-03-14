<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ruyipay.php';

/**
 *
 * * RUYIPAY_QQPAY_PAYMENT_API, ID: 441
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: ruyipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ruyipay_qqpay extends Abstract_payment_api_ruyipay {

	public function getPlatformCode() {
		return RUYIPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ruyipay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		$params['payType'] = self::PAYTYPE_QQPAY;
		$params['bankCode'] = self::BANKCODE_QQPAY;
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
