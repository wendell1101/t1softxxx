<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bingopay.php';
/**
 * BINGOPAY 
 * 
 *
 * BINGOPAY_QQPAY_PAYMENT_API, ID: 652
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.bingopay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bingopay_qqpay extends Abstract_payment_api_bingopay {

	public function getPlatformCode() {
		return BINGOPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bingopay_qqpay';
    }
    
  

	protected function configParams(&$params,&$data, $direct_pay_extra_info) {
				
		$params['bus_no'] = self::DEFAULTNANK_QQPAY;
		$data['productId']=self::DEFAULTNANK_QRCODE;
	
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		//if($this->CI->utils->is_mobile()) {
		//	return $this->processPaymentUrlFormQRCode($params);
		//}else{
			return $this->processPaymentUrlFormQRCode($params);
		//}
	}

}
