<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lepayle.php';

/**
 * 新乐付 LEPAYLE
 * https://cms.lepayle.com/
 *
 * LEPAYLE_WEIXIN_PAYMENT_API, ID: 254
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://api.lepayle.com/gateway/api/scanpay
 * * Extra Info:
 * > {
 * > 	"lepayle_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"lepayle_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_lepayle_weixin extends Abstract_payment_api_lepayle {

	public function getPlatformCode() {
		return LEPAYLE_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lepayle_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		unset($params['redirect_url']);
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
