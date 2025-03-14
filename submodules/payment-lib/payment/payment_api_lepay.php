<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lepay.php';

/**
 * leypay
 * https://lepay.unionpay95516.cc/payapi/
 *
 * LEPAY_PAYMENT_API, ID: 166
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
class Payment_api_lepay extends abstract_payment_api_lepay {

	public function getPlatformCode() {
		return LEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lepay';
	}

	public function getChannelId() {
		return parent::CHANNEL_BANK;
	}
}
