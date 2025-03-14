<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yikuaipay.php';

/**
 * YIKUAIPAY  壹快付
 *
 * * YIKUAIPAY_WEIXIN_PAYMENT_API, ID: 612
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.yikuaipay.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yikuaipay_weixin extends Abstract_payment_api_yikuaipay {

	public function getPlatformCode() {
		return YIKUAIPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yikuaipay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
			$params['Product'] = self::PRODUCT_WEIXIN_SDK;
		}
		else {
			$params['Product'] = self::PRODUCT_WEIXIN_QR;
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
