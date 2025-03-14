<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lepay.php';

/**
 * leypay
 * https://lepay.unionpay95516.cc/payapi/
 *
 * LEPAY_ALIPAY_PAYMENT_API, ID: 167
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
class payment_api_lepay_alipay extends abstract_payment_api_lepay {

	public function getPlatformCode() {
		return LEPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lepay_alipay';
	}

	public function getChannelId() {
		return parent::CHANNEL_ALIPAY;
	}

	# Hide banklist by default, as this API does not support bank selection during form submit
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
