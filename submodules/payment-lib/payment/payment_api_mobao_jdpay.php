<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mobao.php';
/**
 * MOBAO 摩宝(新付)
 * https://xp.7xinpay.com/
 *
 * MOBAO_JDPAY_PAYMENT_API, ID: 774
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

class Payment_api_mobao_jdpay extends Abstract_payment_api_mobao {

	public function getPlatformCode() {
		return MOBAO_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mobao_jdpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['apiName'] = self::MOBAO_APINAME_JDPAY;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

    # Hide bank list dropdown
	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}
}