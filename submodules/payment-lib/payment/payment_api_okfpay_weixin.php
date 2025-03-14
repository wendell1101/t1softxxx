<?php
require_once dirname(__FILE__) . '/abstract_payment_api_okfpay.php';

/**
 *
 * OKFPay OK付-微信
 * http://www.okfpay.com
 *
 * OKFPAY_WEIXIN_PAYMENT_API, ID: 113
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://gateway.okfpay.com/Gate/payindex.aspx
 * * Extra Info
 * > {
 * >	"okfpay_partner" : "## Partner ID ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_okfpay_weixin extends Abstract_payment_api_okfpay {

	public function getPlatformCode() {
		return OKFPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'okfpay_weixin';
	}

	public function getBankType($direct_pay_extra_info) {
		return 'WECHAT';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
