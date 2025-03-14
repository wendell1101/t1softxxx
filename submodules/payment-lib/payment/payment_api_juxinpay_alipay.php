<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juxinpay.php';

/**
 *
 * * JUXINPAY_ALIPAY_PAYMENT_API, ID: 525
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxinpay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juxinpay_alipay extends Abstract_payment_api_juxinpay {

	public function getPlatformCode() {
		return JUXINPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juxinpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		$params['payType'] = self::PAYTYPE_ALIPAY;
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
