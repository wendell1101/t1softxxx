<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sfpay.php';

/**
 *
 * SFPAY 新闪付-支付宝
 *
 * SFPAY_ALIPAY_PAYMENT_API, ID: 284
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
class Payment_api_sfpay_alipay extends Abstract_payment_api_sfpay {

	public function getPlatformCode() {
		return SFPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sfpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['PayID'] = '758';
	}	

	public function getBankCode($direct_pay_extra_info) {
		return '758';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
