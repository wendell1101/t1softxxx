<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sfpay.php';

/**
 * SFPAY 新闪付

 *
 * SFPAY_PAYMENT_API, ID: 283
 *
 * Required Fields:
 *
 * * URL
 * * Account - MemberID
 * * Key - TerminalID
 * * Secret - SecretKey
 *
 *
 * Field Values:
 *
 * * URL: https://gw.sslsf.com/v4.aspx
 *
 * @category Payment

 * @copyright 2013-2022 tot
 */
class Payment_api_sfpay extends Abstract_payment_api_sfpay {

	public function getPlatformCode() {
		return SFPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sfpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {}	

	public function getBankCode($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}
}
