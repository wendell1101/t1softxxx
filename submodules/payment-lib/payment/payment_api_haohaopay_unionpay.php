<?php
require_once dirname(__FILE__) . '/abstract_payment_api_haohaopay.php';
/**
 * HAOHAOPAY 
 * 
 *
 * HAOHAOPAY_UNIONPAY_PAYMENT_API, ID: 760
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.haohaopay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_haohaopay_unionpay extends Abstract_payment_api_haohaopay {

	public function getPlatformCode() {
		return HAOHAOPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'haohaopay_unionpay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
			
		$params['payType'] = self::DEFAULTNANK_UNIONPAY;
		
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
