<?php
require_once dirname(__FILE__) . '/abstract_payment_api_youpay.php';

/**
 * YOUPAY 友付 - 支付寶h5
 * 
 *
 * YOUPAY_ALIPAY_H5_PAYMENT_API, ID: 5297
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.surperpay.com/pay/wapPaySearch
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_youpay_alipay_h5 extends Abstract_payment_api_youpay {

	public function getPlatformCode() {
		return YOUPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'youpay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payType'] = self::PAYTYPE_ALIPAY_WAP;
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
