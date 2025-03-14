<?php
require_once dirname(__FILE__) . '/payment_api_kakaloan_2_alipay_h5.php';
/**
 * kakaloan_2 麒麟支付
 *
 * * KAKALOAN_2_1_ALIPAY_H5_PAYMENT_API, ID: 5115
 * *
 * Required Fields:
 * * URL: http://106.15.82.132:89/Home/Open/AliH5Pay
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kakaloan_2_1_alipay_h5 extends Payment_api_kakaloan_2_alipay_h5 {

	public function getPlatformCode() {
		return KAKALOAN_2_1_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kakaloan_2_1_alipay_h5';
	}
}
