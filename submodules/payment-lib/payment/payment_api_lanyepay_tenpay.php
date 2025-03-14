<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lanyepay.php';

/**
 * LANYEPAY 蓝叶支付 - 财付通
 *
 * * LANYEPAY_TENPAY_PAYMENT_API, ID: 415
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://openapi.lanyepay.cn/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lanyepay_tenpay extends Abstract_payment_api_lanyepay {

	public function getPlatformCode() {
		return LANYEPAY_TENPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lanyepay_tenpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['paytype'] = self::PAYTYPE_TENPAY;
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
