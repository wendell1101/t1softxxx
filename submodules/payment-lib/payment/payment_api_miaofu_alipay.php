<?php
require_once dirname(__FILE__) . '/abstract_payment_api_miaofu.php';

/**
 *
 * Miaofu 秒付
 *
 * MIAOFU_ALIPAY_PAYMENT_API, ID: 128
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.miaofupay.com/gateway
 * * Account: ## Merchant ID ##
 * * Key: ## MD5 Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_miaofu_alipay extends Abstract_payment_api_miaofu {

	public function getPlatformCode() {
		return MIAOFU_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'miaofu_alipay';
	}

	public function getBankType($direct_pay_extra_info) {
		return 'ZHIFUBAO';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

}
