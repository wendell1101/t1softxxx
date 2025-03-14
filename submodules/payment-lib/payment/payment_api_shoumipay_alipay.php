<?php
require_once dirname(__FILE__) . '/abstract_payment_api_shoumipay.php';

/**
 * 收米云 ShoumiPay
 * http://www.shoumipay.com
 *
 * SHOUMIPAY_ALIPAY_PAYMENT_API, ID: 163
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.shoumipay.com/gatepay.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_shoumipay_alipay extends Abstract_payment_api_shoumipay {

	public function getPlatformCode() {
		return SHOUMIPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'shoumipay_alipay';
	}

	public function getChannelId() {
		return parent::CHANNEL_ALIPAY;
	}
}
