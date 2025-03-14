<?php
require_once dirname(__FILE__) . '/abstract_payment_api_anyi51ayf.php';

/** 
 *
 * anyi51ayf  安亿 支付寶
 * 
 * 
 * * 'ANYI51AYF_ALIPAY_PAYMENT_API', ID 958
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://ex.51ayf.com:9000/scan/getQrCode
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_anyi51ayf_alipay extends Abstract_payment_api_anyi51ayf {

	public function getPlatformCode() {
		return ANYI51AYF_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'flightpaying_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

 		$params['payType'] = self::PAYTYPE_ALIPAY;

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
