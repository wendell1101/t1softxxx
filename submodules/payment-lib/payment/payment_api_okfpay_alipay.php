<?php
require_once dirname(__FILE__) . '/abstract_payment_api_okfpay.php';

/**
 *
 * OKFPay OK付-支付宝
 * http://www.okfpay.com
 *
 * OKFPAY_ALIPAY_PAYMENT_API, ID: 112
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://ttokfpay.com/interface/AutoBank/index.aspx
 * * Extra Info
 * > {
 * >	"okfpay_partner" : "## Partner ID ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_okfpay_alipay extends Abstract_payment_api_okfpay {

	public function getPlatformCode() {
		return OKFPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'okfpay_alipay';
	}

	public function getBankType($direct_pay_extra_info) {
		return 'ALIPAY';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
