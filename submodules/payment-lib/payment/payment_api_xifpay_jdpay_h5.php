<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xifpay.php';

/**
 *
 * * XIFPAY_JDPAY_H5_PAYMENT_API, ID: 569
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ebank.xifpay.com
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
class Payment_api_xifpay_jdpay_h5 extends Abstract_payment_api_xifpay {

	public function getPlatformCode() {
		return XIFPAY_JDPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xifpay_jdpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
			$params['defaultbank'] = self::DEFAULTNANK_JDPAY;
			$params['isApp'] = 'h5';
		
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
