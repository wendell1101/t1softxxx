<?php
require_once dirname(__FILE__) . '/abstract_payment_api_h8pay.php';

/**
 * H8PAY (迅付通) - 微信
 * http://www.h8pay.com/
 *
 * H8PAY_WECHAT_PAYMENT_API, ID: 142
 *
 * Required Fields:
 *
 * * URL
 * * Account (Merchant ID)
 * * Key (MD5 signing key)
 *
 * Field Values:
 * * URL: http://wx.h8pay.com/api/pay.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_h8pay_wechat extends Abstract_payment_api_h8pay {
	public function getPlatformCode() {
		return H8PAY_WECHAT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'h8pay_wechat';
	}

	public function getNetWay() {
		return parent::NETWAY_WECHAT;
	}
}