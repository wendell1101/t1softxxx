<?php
require_once dirname(__FILE__) . '/abstract_payment_api_chhpay.php';

/**
 * CHHPAY 畅汇
 * https://t24o.cn/
 *
 * CHHPAY_QUICKPAY_H5_PAYMENT_API, ID: 588
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://changcon.chhpay.com/controller.action
 * * Extra Info:
 * > {
 * > 	"chhpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"chhpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_chhpay_quickpay_h5 extends Abstract_payment_api_chhpay {

	public function getPlatformCode() {
		return CHHPAY_QUICKPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'chhpay_quickpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pa_FrpId'] = self::PAYTYPE_QUICKPAY_H5;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
