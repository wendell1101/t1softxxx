<?php
require_once dirname(__FILE__) . '/abstract_payment_api_antopay.php';

/**
 *
 * ANTOPAY 云安付-支付宝
 * https://pay.antopay.com/antopay.html
 *
 * ANTOPAY_ALIPAY_PAYMENT_API, ID: 236
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://pay.antopay.com/antopay.html
 * * Extra Info
 * > {
 * >	"antopay_partner" : "## Partner ID ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_antopay_alipay extends Abstract_payment_api_antopay {

	public function getPlatformCode() {
		return ANTOPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'antopay_alipay';
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
