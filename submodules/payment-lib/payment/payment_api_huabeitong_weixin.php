<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huabeitong.php';

/**
 * HUABEITONG
 *
 *
 * HUABEITONG_WEIXIN_PAYMENT_API, ID: 5901
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.huabeitong.net/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huabeitong_weixin extends Abstract_payment_api_huabeitong {

	public function getPlatformCode() {
		return HUABEITONG_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huabeitong_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['p4_paytype'] = self::PAYMENT_TYPE_WEXIAN;
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
