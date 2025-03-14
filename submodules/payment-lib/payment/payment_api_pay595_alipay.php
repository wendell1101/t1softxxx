<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay595.php';

/**
 * 595PAY 瞬付
 * http://sh.595pay.com:9090/public/login.jsp
 *
 * PAY595_ALIPAY_PAYMENT_API, ID: 209
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Number
 * * Key - MD5 Key
 *
 * Field Values:
 *
 * * URL: http://139.199.195.194:8080/api/pay.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay595_alipay extends Abstract_payment_api_pay595 {

	public function getPlatformCode() {
		return PAY595_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pay595_alipay';
	}

	protected function getPayNetway() {
		return 'ZFB';
	}
}
