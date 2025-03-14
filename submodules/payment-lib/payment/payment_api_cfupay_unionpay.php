<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cfupay.php';

/**
 *
 * * CFUPAY_UNIONPAY_PAYMENT_API,        ID: 664
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
class Payment_api_cfupay_unionpay extends Abstract_payment_api_cfupay {

	public function getPlatformCode() {
		return CFUPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cfupay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        $params['defaultbank'] = self::PAYTYPE_UNIONQRPAY;
        $params['isApp'] = self::ISAPP_APP;
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
