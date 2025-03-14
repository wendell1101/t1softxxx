<?php
require_once dirname(__FILE__) . '/payment_api_xbeipay.php';

/**
 * XBEI 新贝支付 - 微信
 * Website: http://www.xbeipay.com/
 *
 * XBEI_WEIXIN_PAYMENT_API, ID: 134
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
class Payment_api_xbeipay_weixin extends Payment_api_xbeipay {
	const BANK_CODE_WEIXIN = '100040';

	public function getPlatformCode() {
		return XBEI_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xbei_weixin';
	}

	public function getName() {
		return 'XBEI_WEIXIN';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function getBankCode($direct_pay_extra_info) {
		return self::BANK_CODE_WEIXIN;
	}
}