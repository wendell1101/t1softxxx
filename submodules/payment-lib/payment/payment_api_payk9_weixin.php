<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payk9.php';

/**
 * PAYK9 快支付
 * http://www.payk9.com
 *
 * PAYK9_weixin_PAYMENT_API, ID: 201
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * Key - Terminal ID
 * * Secret - MD5 Key
 *
 * Field Values:
 *
 * * URL: http://payk9.com/payindex
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_payk9_weixin extends Abstract_payment_api_payk9 {

	public function getPlatformCode() {
		return PAYK9_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'payk9_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['PayType'] = 'WECHAT_QRCODE_PAY';
	}
}
