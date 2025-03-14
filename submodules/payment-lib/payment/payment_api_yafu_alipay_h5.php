<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yafu.php';

/**
 * Yafu 雅付
 * https://www.yafupay.com/
 *
 * YAFU_ALIPAY_H5_PAYMENT_API, ID: 431
 *
 *
 * Required Fields:
 *
 * * URL
 * * Key: The user key assigned by Yafu
 *
 *
 * Field Values:
 * * URL: http://pay.yafupay.com/alipay_pay.do
 * * Extra Info:
 * > {
 * >  	"yafu_consumerNo" : "## consumer number ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yafu_alipay_h5 extends Abstract_payment_api_yafu {
	/**
	 * detail: get the platform code from the constant
	 *
	 * @return string
	 */
	public function getPlatformCode() {
		return YAFU_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yafu_alipay_h5';
	}


	public function getPayType() {
		return parent::PAY_TYPE_ALIPAY_H5;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
