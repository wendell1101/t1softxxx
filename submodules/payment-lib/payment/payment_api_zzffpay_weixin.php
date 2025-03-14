<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zzffpay.php';

/**
 * ZZFFPAY 众付
 * http://www.zzffpay.com/
 *
 * * ZZFFPAY_WEIXIN_PAYMENT_API, ID: 153
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 *
 *
 * Field Values:
 *
 * * URL: http://cashier.zzffgateway.com
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zzffpay_weixin extends Abstract_payment_api_zzffpay {

	public function getPlatformCode() {
		return ZZFFPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zzffpay_weixin';
	}

	public function getBankCode($direct_pay_extra_info) {
		return 'WECHATQR';
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
