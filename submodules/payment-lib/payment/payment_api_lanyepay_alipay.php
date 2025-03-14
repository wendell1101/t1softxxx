<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lanyepay.php';

/**
 * LANYEPAY 蓝叶支付 - 支付宝
 *
 * * LANYEPAY_ALIPAY_PAYMENT_API, ID: 413
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
class Payment_api_lanyepay_alipay extends Abstract_payment_api_lanyepay {

	public function getPlatformCode() {
		return LANYEPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lanyepay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			$params['paytype'] = self::PAYTYPE_ALIPAY_WAP;
		}
		else{
			$params['paytype'] = self::PAYTYPE_ALIPAY;
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
