<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yf52.php';

/**
 *
 * * YF52_QUICKPAY_PAYMENT_API, ID: 483
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://way.yf52.com/api/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yf52_quickpay extends Abstract_payment_api_yf52 {

	public function getPlatformCode() {
		return YF52_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yf52_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['p_type'] = self::PAYTYPE_QUICKPAY;
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
