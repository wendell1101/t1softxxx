<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xfuoo.php';

/**
 *
 * * XFUOO_ALIPAY_PAYMENT_API, ID: 381
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ebank.xfuoo.com
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xfuoo_alipay extends Abstract_payment_api_xfuoo {

	public function getPlatformCode() {
		return XFUOO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xfuoo_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['defaultbank'] = self::DEFAULTBANK_ALIPAY;

		if($this->utils->is_mobile()){
			$params['isApp'] = 'H5';
		}
		else{
			$params['isApp'] = 'web';
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
