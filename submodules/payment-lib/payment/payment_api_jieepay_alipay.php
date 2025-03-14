<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jieepay.php';

/**
 * JIEEPAY捷付通
 * http://www.jieepay.com
 *
 * * JIEEPAY_ALIPAY_PAYMENT_API, ID: 137
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 *
 *
 * Field Values:
 *
 * * URL: http://cashier.chinapay360.com/payment/
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jieepay_alipay extends Abstract_payment_api_jieepay {

	public function getPlatformCode() {
		return JIEEPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jieepay_alipay';
	}

	public function getBankCode($direct_pay_extra_info) {
		return 'ALIPAYQR';
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
