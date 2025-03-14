<?php

if(!class_exists('Payment_api_qianwang')){
	require_once dirname(__FILE__) . '/payment_api_qianwang.php';
}

/**
 * Qianwang 千网 - 支付宝
 * http://www.10001000.com/
 *
 * QIANWANG_ALIPAY_PAYMENT_API, ID: 102
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
 * * URL: http://apika.10001000.com/chargebank.aspx
 * * Extra Info:
 * > {
 * >  	"qianwang_partner" : "##partner code##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qianwang_alipay extends Payment_api_qianwang {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return QIANWANG_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'qianwangalipay';
	}

	public function getBankId($order) {
		return '992';
	}

	public function getPlayerInputInfo() {
		return array(
			array('type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

}