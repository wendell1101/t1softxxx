<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ddbill.php';

/**
 * CHINALIGHTPAY 光付
 * https://merchants.chinalightpay.com
 *
 * CHINALIGHTPAY_QQ_PAYMENT_API, ID: 605
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://api.chinalightpay.com/gateway/api/scanpay
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
class Payment_api_chinalightpay_qqpay extends Abstract_payment_api_ddbill {

	public function getPlatformCode() {
		return CHINALIGHTPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'chinalightpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service_type'] = 'tenpay_scan';

		if($this->utils->is_mobile() && $this->getSystemInfo('h5_url')) {
			$params['service_type'] = 'qq_h5api';
		}

		$params['interface_version'] = 'V3.1';
		$params['client_ip'] = $this->getClientIp();
		unset($params['input_charset']);
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
