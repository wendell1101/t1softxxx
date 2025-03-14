<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dfyunpay.php';
/**
 *
 * * DFYUNPAY_WEIXIN_PAYMENT_API, ID: 502
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
class Payment_api_dfyunpay_weixin extends Abstract_payment_api_dfyunpay {

	public function getPlatformCode() {
		return DFYUNPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dfyunpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
			$params['pay_bankcode'] = self::PAYTYPE_WEIXIN;
			$params['tongdao'] = self::TONGDAO_WEIXIN;
			
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
