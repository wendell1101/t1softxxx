<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bofubaopay.php';

/** 
 *
 * 博付宝
 * 
 * 
 * * BOFUBAOPAY_WEIXIN_PAYMENT_API, ID: 459
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://bofubao.qingzhuzi.com/qingpay.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bofubaopay_weixin extends Abstract_payment_api_bofubaopay {

	public function getPlatformCode() {
		return BOFUBAOPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bofubaopay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		$params['bank'] = self::BANK_WEIXIN;
		
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
