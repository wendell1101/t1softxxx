<?php
require_once dirname(__FILE__) . '/abstract_payment_api_uyinpay.php';

/**
 * uyinpay U付
 * http://www.uyinpay.com
 *
 * * UYINPAY_PAYMENT_API, ID: 190
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL
 * 		- Production: https://payment.uyinpay.com/sfpay/payServlet
 * 		- Test: http://pay.miggoo.com/sfpay/payServlet
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
class Payment_api_uyinpay extends Abstract_payment_api_uyinpay {
	const CARD_ATTR_DEBIT = '1';

	public function getPlatformCode() {
		return UYINPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'uyinpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channel'] = 'B2C';
		$params['cardAttr'] = self::CARD_ATTR_DEBIT;

		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['defaultBank'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
