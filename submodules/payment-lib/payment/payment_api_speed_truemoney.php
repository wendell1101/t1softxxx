<?php
require_once dirname(__FILE__) . '/abstract_payment_api_speed.php';

/**
 *
 * speed
 * *
 * *
 * * SPEED_PAYMENT_API, ID: 

 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.63405.com/mctrpc/order/mkReceiptOrder.htm
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_speed_truemoney extends Abstract_payment_api_speed {

	public function getPlatformCode() {
		return SPEED_TRUEMONEY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'speed_true_money';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$bankCodeUse=$this->getSystemInfo('bankCodeUse');
        $params['channleType'] = "2";
        $params['bankCode'] = $bankCodeUse ? "YN_QRIS_ID": ""; 
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
	

	public function getPlayerInputInfo() {
		$getPlayerInputInfo= array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	   	);
        return $getPlayerInputInfo;
    }

}
