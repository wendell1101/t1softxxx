<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lyingpay.php';

/**
 * LYINGPAY 利盈支付 - 微信
 * 
 *
 * LYINGPAY_WEIXIN_PAYMENT_API, ID: 466
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://103.78.122.231:8356/payapi.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lyingpay_weixin extends Abstract_payment_api_lyingpay {

	public function getPlatformCode() {
		return LYINGPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lyingpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
 		if($this->utils->is_mobile()) {
			$params['trade_type'] = self::TRADETYPE_WEIXIN_WAP;
		}
		else {
			$params['trade_type'] = self::TRADETYPE_WEIXIN;
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
