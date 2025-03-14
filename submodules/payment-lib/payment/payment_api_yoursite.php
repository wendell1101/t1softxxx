<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yoursite.php';
/**
* YOURSITE
 *
 * * YOURSITE_PAYMENT_API, ID: 5837
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.payment-connect.com/api/payment
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_yoursite extends Abstract_payment_api_yoursite {

	public function getPlatformCode() {
		return YOURSITE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yoursite';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		// No extra parameters to configure
	}

	public function getPlayerInputInfo() {
		/*
		   1.
		   Valid [float_amount_limit] pattern

		   pattern: "float_amount_limit": "(A|B|C|D|E|F|...)"

		   A: limit amount 1
		   B: limit amount 2
		   C: limit amount 3

		   example: "float_amount_limit": "(1|21|51)"

		   2.
		   show [deposit_hint]  when amount is incorrect
		*/
		$type = $this->getSystemInfo('float_amount_limit') ? 'float_amount_limit' : 'float_amount' ;
		$deposit_hint = $this->getSystemInfo('deposit_hint') ? $this->getSystemInfo('deposit_hint') : '請輸入上方金額';
        $deposit_instruction = $this->getSystemInfo('deposit_instruction') ? $this->getSystemInfo('deposit_instruction') : '';

		if ($type == 'float_amount_limit') {
			return [
				[ 'name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'deposit_hint' => $deposit_hint, 'deposit_instruction' => $deposit_instruction ]
			];
		  }
		  else {
			  return [
				  [ 'name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'deposit_instruction' => $deposit_instruction ],
			  ];
		  }
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormRedirect($params);
	}
}