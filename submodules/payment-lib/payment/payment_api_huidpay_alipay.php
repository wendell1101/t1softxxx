<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huidpay.php';

/**
 *
 * * HUIDPAY_ALIPAY_PAYMENT_API, ID: 335
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
class Payment_api_huidpay_alipay extends Abstract_payment_api_huidpay {

	public function getPlatformCode() {
		return HUIDPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huidpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['defaultbank'] = self::DEFAULTNANK_ALIPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}	
}
