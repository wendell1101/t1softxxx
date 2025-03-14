<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bingopay.php';
/**
 * BINGOPAY 
 * 
 *
 * BINGOPAY_JDPAY_H5_PAYMENT_API, ID: 678
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
class Payment_api_bingopay_jdpay_h5 extends Abstract_payment_api_bingopay {

	public function getPlatformCode() {
		return BINGOPAY_JDPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bingopay_jdpay_h5';
    }
    
  

	protected function configParams(&$params,&$data, $direct_pay_extra_info) {
		
		$params['bus_no'] = self::DEFAULTNANK_JDPAY_H5;
		$data['productId']=self::DEFAULTNANK_QRCODE;
	
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		
		//	return $this->processPaymentUrlFormQRCode($params);
		
		return $this->processPaymentUrlFormPost($params);
		
	}

}
