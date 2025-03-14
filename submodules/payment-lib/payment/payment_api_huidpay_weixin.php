<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huidpay.php';

/**
 *
 * * HUIDPAY_WEIXIN_PAYMENT_API, ID: 336
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ebank.huihuidpay.com 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 * > {
 * >    "sellerEmail" : "## Seller email address, system will show you when the merchant opens ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huidpay_weixin extends Abstract_payment_api_huidpay {

	public function getPlatformCode() {
		return HUIDPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huidpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['defaultbank'] = self::DEFAULTNANK_WEIXIN;
	}
	
	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}	
}
