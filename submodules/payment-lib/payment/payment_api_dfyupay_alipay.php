<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dfyupay.php';
/**
 *
 * * DFYUNPAY_ALIPAY_PAYMENT_API, ID: 503
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.adsstore.cn//Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2017-2022 tot
 */
class Payment_api_dfyupay_alipay extends Abstract_payment_api_dfyupay {

	public function getPlatformCode() {
		return DFYUPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dfyupay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
			//$params['tongdao'] = self::PAYTYPE_ALIPAY;
			$params['pay_bankcode'] =self::PAYTYPE_ALIPAY;

			
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
