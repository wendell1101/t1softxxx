<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mobao.php';
/**
 * MOBAO 摩宝(新付)
 * https://xp.7xinpay.com/
 *
 * MOBAO_QQPAY_PAYMENT_API, ID: 769
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

class Payment_api_mobao_qqpay extends Abstract_payment_api_mobao {

	public function getPlatformCode() {
		return MOBAO_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mobao_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->utils->is_mobile()) {
			#mobile_scan_qrcode = true 的話一律出QRcode
			if($this->getSystemInfo('mobile_scan_qrcode')){
				$params['apiName'] = self::MOBAO_APINAME_QQPAY;
				$params['customerIP'] = $this->getClientIp();
			}
			else{
				$params['apiName'] = self::MOBAO_APINAME_QQPAY_WAP;
			}
		}
		else{
		    $params['apiName'] = self::MOBAO_APINAME_QQPAY;
			$params['customerIP'] = $this->getClientIp();
		}
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