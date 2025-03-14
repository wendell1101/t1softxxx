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
class Payment_api_speed_banktransfer extends Abstract_payment_api_speed {

	public function getPlatformCode() {
		return SPEED_BANKTRANSFER_PAYMENT_API;
	}

	public function getPrefix() {
		return 'speed_banktransfer';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$bankCodeUse=$this->getSystemInfo('bankCodeUse');
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
	        	$params['bankCode'] = $extraInfo['field_required_bank_code'];
	        }
		}
		
        $params['channleType'] = "1";
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {
		$getPlayerInputInfo= array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	   	);
		if($this->getSystemInfo('bankCodeUse')){
			$newArray = array('name' => 'field_required_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank','list' => $this->getBankList());
			array_push($getPlayerInputInfo, $newArray);			
		}
        return $getPlayerInputInfo;
    }

}
