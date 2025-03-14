<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sfpay.php';

/**
 *
 * SFPAY 新闪付-微信
 *
 * SFPAY_WEIXIN_PAYMENT_API, ID: 285
 *
 * * URL
 * * Account - MemberID
 * * Key - TerminalID
 * * Secret - SecretKey
 *
 * Field Values:
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sfpay_weixin extends Abstract_payment_api_sfpay {

	public function getPlatformCode() {
		return SFPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sfpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['PayID'] = '57';
	}	

	public function getBankCode($direct_pay_extra_info) {
		return '57';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
