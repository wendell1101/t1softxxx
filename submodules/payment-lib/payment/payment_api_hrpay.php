<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hrpay.php';

/**
 * HRPAY 华仁
 * http://www.hr-pay.com
 *
 * * HRPAY_PAYMENT_API, ID: 146
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
class Payment_api_hrpay extends Abstract_payment_api_hrpay {
	public function getPlatformCode() {
		return HRPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hrpay';
	}

	# Ref: Documentation page 1
	protected function getPageCode() {
		return parent::PAGECODE_BANK;
	}

	# get selected bank id
	protected function configParams(&$params, $direct_pay_extra_info) {
		if (empty($direct_pay_extra_info)) {
			return;
		}

		$extraInfo = json_decode($direct_pay_extra_info, true);
		if (!empty($extraInfo) && array_key_exists('bank', $extraInfo)) {
			$params['v_bankno'] = $extraInfo['bank'];
		}
	}
}
