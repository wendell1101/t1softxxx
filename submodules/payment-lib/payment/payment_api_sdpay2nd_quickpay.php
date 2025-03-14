<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay2nd.php';

/**
 * SDPay_2ND 速达支付 2ND 快捷
 * http://www.sdsystem.hk
 *
 * SDPAY2ND_QUICKPAY_PAYMENT_API, ID: 5006
 *
 *
 * Required Fields:
 *
 * * URL
 * * Key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://api.officenewline.org:1010/ToService.aspx
 * * Key: ## SDPay MD5 key ##
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
class Payment_api_sdpay2nd_quickpay extends Abstract_payment_api_sdpay2nd {
	public function getPlatformCode() {
		return SDPAY2ND_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sdpay2nd_quickpay';
	}
}