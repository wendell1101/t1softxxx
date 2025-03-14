<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ddbill.php';

/**
 * CHINALIGHTPAY 光付
 * https://merchants.chinalightpay.com 
 *
 * CHINALIGHTPAY_PAYMENT_API, ID: 602
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://pay.chinalightpay.com/gateway?input_charset=UTF-8
 * * Extra Info:
 * > {
 * > 	"ddbill_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"ddbill_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_chinalightpay extends Abstract_payment_api_ddbill {

	public function getPlatformCode() {
		return CHINALIGHTPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ddbill';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service_type'] = 'direct_pay';
		$params['interface_version'] = 'V3.0';

		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
