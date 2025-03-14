<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hrpay.php';

/**
 * HRPAY 华仁
 * http://www.hr-pay.com
 *
 * * HRPAY_ALIPAY_PAYMENT_API, ID: 147
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - MD5 key
 * * ExtraInfo - include pub key and priv key
 *
 * Field Values:
 *
 * * URL: http://api.hr-pay.com/PayInterface.aspx
 * * Extra Info:
 * > {
 * > 	"hrpay_priv_key" : "## path to merchant's private key ##",
 * > 	"hrpay_pub_key" : "## path to API's public key ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hrpay_alipay extends Abstract_payment_api_hrpay {
	public function getPlatformCode() {
		return HRPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hrpay_alipay';
	}

	# Ref: Documentation page 1
	protected function getPageCode() {
		return parent::PAGECODE_ALIPAY;
	}

	# Specify special param
	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['v_app'] = ''; # if this is given 'app', the url will return the QRCode data instead of redirecting
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
