<?php
require_once dirname(__FILE__) . '/abstract_payment_api_qf985pay.php';

/**
 * leypay
 * https://lepay.unionpay95516.cc/payapi/
 *
 * LEPAY_WEIXIN_PAYMENT_API, ID: 168
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://openapi.unionpay95516.cc/pre.lepay.api/order/add
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qf985pay_weixin extends abstract_payment_api_qf985pay {
    const NETWAY = 'WX';

	public function getPlatformCode() {
		return QF985PAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'qf985pay_weixin';
	}

	public function getChannelId() {
		return parent::CHANNEL_WEIXIN;
	}

	# Hide banklist by default, as this API does not support bank selection during form submit
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
