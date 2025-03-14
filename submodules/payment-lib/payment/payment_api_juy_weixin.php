<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juy.php';

/**
 *
 * juy 聚源-微信
 *
 * JUY_WEIXIN_PAYMENT_API, ID: 105
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: http://pay.juypay.com/PayBank.aspx
 * * Extra Info:
 * > {
 * >    "juy_partner" : "## Partner ID ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juy_weixin extends Abstract_payment_api_juy {

	public function getPlatformCode() {
		return JUY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juy_weixin';
	}

	public function getBankType($direct_pay_extra_info) {
		return 'WEIXIN';
	}

	# Hide bank selection
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
