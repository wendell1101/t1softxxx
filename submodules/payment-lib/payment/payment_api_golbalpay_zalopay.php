<?php
require_once dirname(__FILE__) . '/abstract_payment_api_golbalpay.php';

/**
 *
 * * GOLBALPAY_ZALOPAY_PAYMENT_API, ID: 6014
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ckus.kighj.com/ty/orderPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_golbalpay_zalopay extends Abstract_payment_api_golbalpay {

	public function getPlatformCode() {
		return GOLBALPAY_ZALOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'golbalpay_zalopay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['busi_code'] = self::BUSICODE_ZALOPAY;
	}	

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormRedirect($params);
	}
}
