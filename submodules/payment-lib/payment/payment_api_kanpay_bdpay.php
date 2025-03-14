<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kanpay.php';
/**
 * KANPAY 
 * 
 *
 * KANPAY_BDPAY_PAYMENT_API, ID: 796
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.kanpay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kanpay_bdpay extends Abstract_payment_api_kanpay {

	public function getPlatformCode() {
		return KANPAY_BDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kanpay_bdpay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
		
		if($this->CI->utils->is_mobile()) {
			$params['pay_bankcode'] = $this->getSystemInfo("phone_code");
		}else{
			$params['pay_bankcode'] = $this->getSystemInfo("web_code");
		}

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
