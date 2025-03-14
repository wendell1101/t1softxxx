<?php
require_once dirname(__FILE__) . '/abstract_payment_api_anyi51ayf.php';

/** 
 *
 * anyi51ayf  安亿 銀聯
 * 
 * 
 * * 'ANYI51AYF_UNIONPAY_PAYMENT_API', ID 960
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
class Payment_api_anyi51ayf_unionpay extends Abstract_payment_api_anyi51ayf {

	public function getPlatformCode() {
		return ANYI51AYF_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'anyi51ayf_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

 		$params['payType'] = self::PAYTYPE_UNIONPAY;

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
