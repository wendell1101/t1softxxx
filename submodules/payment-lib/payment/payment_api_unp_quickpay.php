<?php
require_once dirname(__FILE__) . '/abstract_payment_api_unp.php';

/**
 *
 * UNP UNP支付-支付宝
 * http://wiki.unpayonline.com:8800/doku.php?id=api_for_unp
 *
 * UNP_QUICKPAY_PAYMENT_API, ID: 272
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://center.qpay888.com/Bank
 * * Extra Info
 * > {
 * >	"unp_partner" : "## Partner ID ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_unp_quickpay extends Abstract_payment_api_unp {

	public function getPlatformCode() {
		return UNP_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'unp_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['tyid'] = self::PAYMENT_TYPE_QUICKPAY ;
	}

	public function getBankType($direct_pay_extra_info) {
		return '1005';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
