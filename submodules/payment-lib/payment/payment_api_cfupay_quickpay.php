<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cfupay.php';

/**
 *
 * * CFUPAY_QUICKPAY_PAYMENT_API,        ID: 659
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxin
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cfupay_quickpay extends Abstract_payment_api_cfupay {

	public function getPlatformCode() {
		return CFUPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cfupay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        $params['defaultbank'] = self::PAYTYPE_QUICKPAY;
        $params['paymethod'] = self::PAYMETHOD_BANKPAY;
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
