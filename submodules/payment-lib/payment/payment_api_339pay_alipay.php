<?php
require_once dirname(__FILE__) . '/abstract_payment_api_339pay.php';

/**
 * 339PAY叁叁玖
 * http://www.sz339pay.com:9001/
 *
 * * 339PAY_ALIPAY_PAYMENT_API, ID: 21
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 *
 * Field Values:
 *
 * * URL: /aliApi.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_339pay_alipay extends Abstract_payment_api_339pay {

	public function getPlatformCode() {
		return _339PAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '339pay_alipay';
	}

	public function getBankCode($direct_pay_extra_info) {
		return 'ALIPAYQR';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		unset($params['p9_FrpCode']);
		unset($params['pa_OrderPeriod']);
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}	
}
