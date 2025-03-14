<?php
require_once dirname(__FILE__) . '/abstract_payment_api_goldenpay88.php';

/**
 * 金付卡 GOLDENPAY88
 * https://sup.goldenpay88.com/
 *
 * GOLDENPAY88_UNIONPAY_PAYMENT_API, ID: 403
 *
 * Required Fields:
 * * URL
 * * Extra Info:
 * * {
 * *    "terminal_id"
 * *    "merchant_id"
 * *    "goldenpay88_pub_key"
 * *    "goldenpay88_priv_key"
 * * }
 *
 *
 * Field Values:
 * * URL: https://www.goldenpay88.com/gateway/orderPay
 * * Extra Info:
 * * {
 * *    "terminal_id": ## Terminal ID ##,
 * *    "merchant_id": ## Merchant ID ##,
 * *    "goldenpay88_pub_key" : "## pem formatted public key (escaped) ##",
 * *    "goldenpay88_priv_key" : "## pem formatted private key (escaped) ##"
 * * }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_goldenpay88_unionpay extends Abstract_payment_api_goldenpay88 {

	public function getPlatformCode() {
		return GOLDENPAY88_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'goldenpay88_unionpay';
	}

	public function getName() {
		return 'GOLDENPAY88_UNIONPAY';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payType'] = self::PAY_TYPE_CODE_UNIONPAY;
		unset($params['appSence']);
		unset($params['syncURL']);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
