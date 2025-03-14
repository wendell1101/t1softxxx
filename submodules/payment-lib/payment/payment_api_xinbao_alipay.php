<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinbao.php';

/**
 *
 * Xinbao 鑫宝-支付宝
 *
 * XINBAO_ALIPAY_PAYMENT_API, ID: 106
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
class Payment_api_xinbao_alipay extends Abstract_payment_api_xinbao {

	public function getPlatformCode() {
		return XINBAO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xinbao_alipay';
	}

	public function getPayType() {
		return 'ALIPAYQR';
	}

	# Hide bank selection
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
