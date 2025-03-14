<?php
require_once dirname(__FILE__) . '/abstract_payment_api_shadd.php';
/**
 * SHADD 刷得多
 *
 * * SHADD_ALIPAY_PAYMENT_API (5778)
 * * Abstract_payment_api_shadd
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://4536251.net/api/transaction
 * * Account: ## Merchant Name ##
 * * Key: ## Merchant Access Token ##
 *
 * @see		abstract_payment_api_shadd.php
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_shadd_alipay extends Abstract_payment_api_shadd {

	public function getPlatformCode() {
		return SHADD_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'shadd_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_type'] = $this->getSystemInfo('pay_type');
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
		if ($type == 'float_amount_limit') {
			return [
				[ 'name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'deposit_hint' => $deposit_hint
				]
			];
		  }
		  else {
			  return [
				  [ 'name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09' ],
			  ];
		  }
	  }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}