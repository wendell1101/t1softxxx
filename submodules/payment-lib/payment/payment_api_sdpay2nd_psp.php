<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay2nd.php';

/**
 * SDPay_2ND 速达支付 2ND
 * http://www.sdsystem.hk
 *
 * SDPAY2ND_PSP_PAYMENT_API, ID: 125
 *
 *
 * Required Fields:
 *
 * * Key
 * * URL
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Key: ## SDPay MD5 key ##
 * * URL: http://api.officenewline.org:83/ToService.aspx
 * * Extra Info
 * > {
 * >     "sdpay_merchantId": "## merchant ID ##",
 * >     "sdpay_key1": "## RSA key 1 ##",
 * >     "sdpay_key2": "## RSA key 2 ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay2nd_psp extends Abstract_payment_api_sdpay2nd {
	public function getPlatformCode() {
		return SDPAY2ND_PSP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sdpay2nd_psp';
	}
}