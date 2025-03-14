<?php
require_once dirname(__FILE__) . '/abstract_payment_api_onewpay.php';

/**
 *
 * 1WPAY 在線寶
 * http://www.1wpay.com/
 *
 * 1WPAY_WEIXIN_PAYMENT_API, ID: 231
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_onewpay_weixin extends Abstract_payment_api_onewpay {

	public function getPlatformCode() {
		return ONEWPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'onewpay_weixin';
	}

	public function getBankType($direct_pay_extra_info) {
		return '2';
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
