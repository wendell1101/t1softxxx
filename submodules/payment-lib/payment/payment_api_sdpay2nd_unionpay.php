<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay2nd.php';

/**
 * SDPay_2ND 速达支付 2ND
 * http://www.sdsystem.hk
 *
 * SDPAY2ND_UNIONPAY_PAYMENT_API, ID: 122
 *
 *
 * Required Fields:
 *
 * * Key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Key: ## SDPay MD5 key ##
 * * Extra Info
 * > {
 * >     "sdpay_pc_url" : "http://api.officenewline.org:1010/ToService.aspx",
 * >     "sdpay_mobile_url" : "http://api.officenewline.org:1010/PMToService.aspx",
 * >     "sdpay_mobile_url_noplugin" : "http://api.officenewline.org:1010/PMToService2.aspx",
 * >     "sdpay_merchantId": "## merchant ID ##",
 * >     "sdpay_key1": "## RSA key 1 ##",
 * >     "sdpay_key2": "## RSA key 2 ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay2nd_unionpay extends Abstract_payment_api_sdpay2nd {
	public function getPlatformCode() {
		return SDPAY2ND_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sdpay2nd_unionpay';
	}

	public function getPaymentUrl() {
		if(!$this->utils->is_mobile()) {
			return $this->getSystemInfo("sdpay_pc_url");
		} else {
			return $this->getSystemInfo("sdpay_mobile_url_noplugin");
		}
	}

}