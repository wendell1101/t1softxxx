<?php
require_once dirname(__FILE__) . '/payment_api_rfupay.php';

/**
 * 
 * RFUPAY_WEIXIN_PAYMENT_API, ID: 65
 *
 * The wechat pay implementation of RFUPay.
 * 
 * _Note: Minimum payment is 2 CNY._
 *
 * @see Payment_api_rfupay
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rfupay_weixin extends Payment_api_rfupay {

	public function getPlatformCode() {
		return RFUPAY_WEIXIN_PAYMENT_API;
	}

	protected function getAppType() {
		return 'WECHAT';
	}

	protected function getBankId($order) {
		return 'wechat';
	}

	# Override the bank list function to hide bank list dropdown (type=float_amount)
	public function getPlayerInputInfo() {
		return array(
			array('type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}