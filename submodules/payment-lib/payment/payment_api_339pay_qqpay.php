<?php
require_once dirname(__FILE__) . '/abstract_payment_api_339pay.php';

/**
 * 339PAY叁叁玖 - QQ
 * http://www.sz339pay.com:9001/
 *
 * * 339PAY_QQPAY_PAYMENT_API, ID: 224
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 *
 * Field Values:
 *
 * * URL: http://test-pay.sz339pay.com:9001/trade/qqApi.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_339pay_qqpay extends Abstract_payment_api_339pay {

	public function getPlatformCode() {
		return _339PAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '339pay_qqpay';
	}

	public function getBankCode($direct_pay_extra_info) {
		return 'QQPAYQR';
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
