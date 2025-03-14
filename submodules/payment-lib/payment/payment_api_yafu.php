<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yafu.php';

/**
 * Yafu 雅付
 * https://www.yafupay.com/
 *
 * YAFU_PAYMENT_API, ID: 94
 *
 *
 * Required Fields:
 *
 * * URL
 * * Key: The user key assigned by Yafu
 *
 *
 * Field Values:
 * * URL: http://pay.yafupay.com/bank_pay.do
 * * Extra Info:
 * > {
 * >  	"yafu_consumerNo" : "## consumer number ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yafu extends Abstract_payment_api_yafu {
	const PAY_TYPE_BANK = '0101';
	
	/**
	 * detail: get the platform code from the constant
	 *
	 * @return string
	 */
	public function getPlatformCode() {
		return YAFU_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yafu';
	}

	public function getPayType() {
		return parent::PAY_TYPE_BANK;
	}
}
