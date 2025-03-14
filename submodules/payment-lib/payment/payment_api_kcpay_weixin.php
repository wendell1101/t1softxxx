<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kcpay.php';

/**
 * KCPAY 卡诚 - 微信
 * 
 *
 * KCPAY_WEIXIN_PAYMENT_API, ID: 208
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://api.kcpay.net/PayWeiXin_System.aspx
 * * Extra Info
 * > {
 * >	"kcpay_partner" : "##Partner ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kcpay_weixin extends Abstract_payment_api_kcpay {

	public function getPlatformCode() {
		return KCPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kcpay_weixin';
	}

	public function getBankType($direct_pay_extra_info){
		return $this->utils->is_mobile() ? parent::BANK_TYPE_WEIXIN_WAP : parent::BANK_TYPE_WEIXIN;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}