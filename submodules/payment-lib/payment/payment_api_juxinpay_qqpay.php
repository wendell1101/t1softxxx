<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juxinpay.php';

/**
 *
 * * JUXINPAY_QQPAY_PAYMENT_API, ID: 529
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
class Payment_api_juxinpay_qqpay extends Abstract_payment_api_juxinpay {

	public function getPlatformCode() {
		return JUXINPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juxinpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		$params['payType'] = ($this->utils->is_mobile()) ? self::PAYTYPE_QQPAY_H5 : self::PAYTYPE_QQPAY;
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
