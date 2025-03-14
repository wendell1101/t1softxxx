<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yspay.php';

/**
 * YSPAY 广州银商 - 支付宝
 *
 *
 * YSPAY_ALIPAY_PAYMENT_API, ID: 725
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.yspay.co/pay/api.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yspay_alipay extends Abstract_payment_api_yspay {

	public function getPlatformCode() {
		return YSPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yspay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['zftd'] = self::PAYTYPE_ALIPAY;
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
