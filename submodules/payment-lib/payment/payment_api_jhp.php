<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jhp.php';

/**
 * JHP
 *
 * * JHP_PAYMENT_API, ID: 5591
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.fastpay365.com/pgw.shtml
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jhp extends Abstract_payment_api_jhp {

	public function getPlatformCode() {
		return JHP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jhp';		
	}

	protected function configParams(&$params, $direct_pay_extra_info) {	
		$params['payTypeId'] = self::PAY_TYPE_ID_BANKCARD;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {		
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),			
		);
	}

	
}
