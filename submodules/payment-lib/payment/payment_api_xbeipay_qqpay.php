<?php
require_once dirname(__FILE__) . '/payment_api_xbeipay.php';

/**
 * XBEI 新贝支付 - QQ钱包
 * Website: http://www.xbeipay.com/
 *
 * XBEI_QQPAY_PAYMENT_API, ID: 211
 *
 * Required Fields:
 *
 * * URL
 * * Key - partner code
 * * Secret - secret key
 *
 *
 * Field Values:
 *
 * * URL: https://gws.xbeionline.com/Gateway/XbeiPay
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xbeipay_qqpay extends Payment_api_xbeipay {
	const BANK_CODE_QQPAY = '100068';

	public function getPlatformCode() {
		return XBEI_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xbei_qqpay';
	}

	public function getName() {
		return 'XBEI_QQPAY';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function getBankCode($direct_pay_extra_info) {
		return self::BANK_CODE_QQPAY;
	}
}