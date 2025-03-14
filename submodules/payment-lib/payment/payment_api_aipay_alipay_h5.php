<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aipay.php';

/** 
 *
 * aipay 艾付 微信
 * 
 * 
 * * 'AIPAY_ALIPAY_H5_PAYMENT_API', ID 5039
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.goodatpay.com/gateway/pay.jsp
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aipay_alipay_h5 extends Abstract_payment_api_aipay {

	public function getPlatformCode() {
		return AIPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'aipay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
    	$params['pay_mode'] = self::PAY_MODE_H5;
       	$params['bank_code'] = self::SCANTYPE_ALIPAY_H5;
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
