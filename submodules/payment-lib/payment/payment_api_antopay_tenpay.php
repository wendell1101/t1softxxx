<?php
require_once dirname(__FILE__) . '/abstract_payment_api_antopay.php';

/**
 *
 * ANTOPAY 云安付-QQ
 * https://pay.antopay.com/antopay.html
 *
 * ANTOPAY_TENPAY_PAYMENT_API, ID: 238
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
class Payment_api_antopay_tenpay extends Abstract_payment_api_antopay {

	public function getPlatformCode() {
		return ANTOPAY_TENPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'antopay_tenpay';
	}

	public function getBankType($direct_pay_extra_info) {
		return 'TENPAY';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
