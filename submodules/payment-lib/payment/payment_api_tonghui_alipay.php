<?php
require_once dirname(__FILE__) . '/payment_api_tonghui.php';

/**
 * TONGHUI-WECHAT 通汇卡 (支付宝)
 * http://www.41.cn
 *
 * TONGHUI_ALIPAY_PAYMENT_API, ID: 75
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://pay.41.cn/gateway
 * * Extra Info:
 * > {
 * >  	"tonghui_merchant_code" : "##merchant code##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tonghui_alipay extends Payment_api_tonghui {

	public function getPlatformCode() {
		return TONGHUI_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tonghui';
	}

	public function getBankCode($order) {
		if(!empty($this->getSystemInfo('bank_code'))){
			return $this->getSystemInfo('bank_code');
		}
		return 'ZHIFUBAO';
	}

	# Hide bank selection
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}