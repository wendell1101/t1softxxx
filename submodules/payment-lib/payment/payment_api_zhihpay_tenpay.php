<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zhihpay.php';

/**
 * ZHIHPAY 智汇付
 * http://www.zhihpay.com
 *
 * ZHIHPAY_TENPAY_PAYMENT_API, ID: 179
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://api.zhihpay.com/gateway/api/scanpay
 * * Extra Info:
 * > {
 * > 	"zhihpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"zhihpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zhihpay_tenpay extends Abstract_payment_api_zhihpay {

	public function getPlatformCode() {
		return ZHIHPAY_TENPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zhihpay_tenpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service_type'] = 'tenpay_scan';
		$params['interface_version'] = 'V3.1';
		$params['client_ip'] = $this->getClientIp();
		unset($params['return_url']);
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
