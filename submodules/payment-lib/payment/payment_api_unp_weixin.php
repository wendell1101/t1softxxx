<?php
require_once dirname(__FILE__) . '/abstract_payment_api_unp.php';

/**
 *
 * UNP UNP支付-微信
 * http://wiki.unpayonline.com:8800/doku.php?id=api_for_unp
 *
 * UNP_WEIXIN_PAYMENT_API, ID: 273
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
class Payment_api_unp_weixin extends Abstract_payment_api_unp {

	public function getPlatformCode() {
		return UNP_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'unp_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			$params['tyid'] = self::PAYMENT_TYPE_WEIXIN_H5 ;
		}
		else {
			$params['tyid'] = self::PAYMENT_TYPE_WEIXIN;
		}
	}

	public function getBankType($direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			return '1007';
		}
		else {
			return '991';
		}
		
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
