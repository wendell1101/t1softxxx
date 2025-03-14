<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinbao.php';

/**
 *
 * Xinbao 鑫宝-微信
 *
 * XINBAO_WEIXIN_PAYMENT_API, ID: 107
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: http://api.pk767.com/pay
 * * Extra Info:
 * > {
 * >  	"xinbao_merchant_code" : "## Merchant Code ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xinbao_weixin extends Abstract_payment_api_xinbao {

	public function getPlatformCode() {
		return XINBAO_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xinbao_weixin';
	}

	public function getPayType() {
		return 'WECHATQR';
	}

	# Hide bank selection
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
