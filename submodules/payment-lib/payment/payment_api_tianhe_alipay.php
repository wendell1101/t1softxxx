<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianhe.php';
/**
 * TIANHE
 *
 * * TIANHE_ALIPAY_PAYMENT_API, ID: 5769
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.22222269.com/index/unifiedorder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tianhe_alipay extends Abstract_payment_api_tianhe {

	public function getPlatformCode() {
		return TIANHE_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tianhe_alipay';
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
	  $type = $this->getSystemInfo('float_amount_limit')? 'float_amount_limit' : 'float_amount' ;
	  $deposit_hint = $this->getSystemInfo('deposit_hint')?$this->getSystemInfo('deposit_hint'):'請輸入上方金額';
	  if($type == 'float_amount_limit'){
		  return array(
			  array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'deposit_hint' => $deposit_hint),
		  );
	  }else{
		  return array(
			  array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09'),
		  );
	  }
  }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}