<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mobao.php';
/**
 * MOBAO 摩宝(新付)
 * https://xp.7xinpay.com/
 *
 * MOBAO_ALIPAY_PAYMENT_API, ID: 245
 *
 * General behavior includes :
 * * Recieving callbacks
 * * Generate payment forms
 * * Checking of callback orders
 * * Get bank details
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
 * * Live URL: http://trade.7xinpay.com/cgi-bin/netpayment/pay_gate.cgi
 * * Sandbox URL: http://trade.7xinpay.com/cgi-bin/netpayment/pay_gate.cgi
 * * Extra Info
 * > {
 * >     "mobao_apiVersion": "1.0.0.0",
 * >     "mobao_platformID": "##platform ID##",
 * >     "mobao_merchNo": "##merchant ID##",
 * >     "callback_host" : ""
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_mobao_alipay extends Abstract_payment_api_mobao {
/*
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}
*/
	public function getPlatformCode() {
		return MOBAO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mobao_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->utils->is_mobile()) {
			#mobile_scan_qrcode = true 的話一律出QRcode
			$params['apiName'] = ($this->getSystemInfo('mobile_scan_qrcode')) ? self::MOBAO_APINAME_ALIPAY : self::MOBAO_APINAME_ALIPAY_WAP;
		}
		else{
			$params['apiName'] = self::MOBAO_APINAME_ALIPAY;
	    }
		$params['customerIP'] = $this->getClientIp();
	}

	protected function processPaymentUrlForm($params) {
		if($this->utils->is_mobile()) {
			return ($this->getSystemInfo('mobile_scan_qrcode')) ? $this->processPaymentUrlFormQRCode($params) : $this->processPaymentUrlFormPost($params);
		}
		else {
			return $this->processPaymentUrlFormQRCode($params);
		}
	}

    # Hide bank list dropdown
	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}
}