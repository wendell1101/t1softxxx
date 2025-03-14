<?php
require_once dirname(__FILE__) . '/abstract_payment_api_haoyunpay.php';

/**
 * 
 * 
 * HAOYUNPAY_UNIONPAY_PAYMENT_API, ID:5459
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Live key
 * 
 * Field Values:
 * * URL: https://g88api.com
 * * Account: ## Merchant ID ##
 * * Key: ## Merchant Key ##
 * 
 * 
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_haoyunpay_unionpay extends Abstract_payment_api_haoyunpay {
    	public function getPlatformCode() {
		return HAOYUNPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'haoyunpay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pt'] = self::PAYTYPE_UNIONPAY;
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