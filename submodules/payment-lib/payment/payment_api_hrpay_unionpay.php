<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hrpay.php';

/**
 * HRPAY 华仁 - 快捷支付
 * http://www.hr-pay.com
 *
 * * HRPAY_UNIONPAY_PAYMENT_API, ID: 149
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
class Payment_api_hrpay_unionpay extends Abstract_payment_api_hrpay {
	public function getPlatformCode() {
		return HRPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hrpay_unionpay';
	}

	# Ref: Documentation page 1
	protected function getPageCode() {
		return parent::PAGECODE_UNIONPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# Specify special param
	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['v_bankno'] = '104100000004'; # Hardcode a bankno for the validation to pass, bank is not needed
	}
}
