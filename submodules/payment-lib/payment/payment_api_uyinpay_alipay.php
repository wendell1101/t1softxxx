<?php
require_once dirname(__FILE__) . '/abstract_payment_api_uyinpay.php';

/**
 * uyinpay U付
 * http://www.uyinpay.com
 *
 * UYINPAY_ALIPAY_PAYMENT_API, ID: 191
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * Scan Code URL
 * 		- Production: https://payment.uyinpay.com/sfpay/scanCodePayServlet
 *   	- Test: http://pay.miggoo.com/sfpay/scanCodePayServlet
 * * Test Merchant ID: GWP_TEST
 * * Extra Info:
 * > {
 * > 	"uyinpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"uyinpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_uyinpay_alipay extends Abstract_payment_api_uyinpay {
	const SCAN_TYPE_ALIPAY = '10000001';

	public function getPlatformCode() {
		return UYINPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'uyinpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['scanType'] = self::SCAN_TYPE_ALIPAY;
		unset($params['returnUrl']);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlQRCode($params);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
