<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yinbao.php';

/**
 * YINBAO 银宝 - 微信
 * http://www.9vpay.com/
 *
 * YINBAO_WEIXIN_PAYMENT_API, ID: 144
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
 * * URL: http://wytj.9vpay.com/PayBank.aspx
 * * Extra Info
 * > {
 * >	"yinbao_partner" : "##Partner ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yinbao_weixin extends Abstract_payment_api_yinbao {

	public function getPlatformCode() {
		return YINBAO_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yinbao_weixin';
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