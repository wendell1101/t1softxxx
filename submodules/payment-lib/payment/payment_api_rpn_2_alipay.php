<?php
require_once dirname(__FILE__) . '/payment_api_rpn_alipay.php';

/**
 * RPN
 *
 * * RPN_2_ALIPAY_PAYMENT_API, ID: 5417
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://deposit.paylomo.net/pay.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_2_alipay extends Payment_api_rpn_alipay {

	public function getPlatformCode() {
		return RPN_2_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn_2_alipay';
	}
}
