<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sulongpay.php';
/**
 * SULONGPAY 
 *
 * * SULONGPAY_ALIPAY_H5_PAYMENT_API,  ID: 5272
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info:
 * > { 
 * >    "gateway_url": "http://pay.sulongpay.com/gateway/payment",
 * >    "useFormPost": ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sulongpay_alipay_h5 extends Abstract_payment_api_sulongpay {

	public function getPlatformCode() {
		return SULONGPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sulongpay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
            $params['trade_type'] = self::PAYTYPE_ALIPAY;
	}

	protected function processPaymentUrlForm($params) {
		$useFormPost = $this->getSystemInfo('useFormPost');
		if($useFormPost == true){
			return $this->processPaymentUrlFormPost($params);
		}
		else{
			return $this->processPaymentUrlFormQRCode($params);
		}
	}
	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
