<?php

if(!class_exists('Payment_api_ehking')){
	require_once dirname(__FILE__) . '/payment_api_ehking.php';
}

/**
 * EHKING-WECHAT 易汇金 (微信)
 * http://www.ehking.com
 *
 * EHKING_WEIXIN_PAYMENT_API, ID: 88
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Live URL: https://api.ehking.com/onlinePay/order
 * * Sandbox URL: https://api.ehking.com/onlinePay/order
 * * Extra Info
 * > {
 * >     "ehking_merchantId" : "##Merchant ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ehking_weixin extends Payment_api_ehking {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return EHKING_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ehkingweixin';
	}

	protected function getBankId($order) {
		return 'SCANCODE-WEIXIN_PAY-P2P';
	}

	public function getPlayerInputInfo() {
		return array(
			array('type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

}