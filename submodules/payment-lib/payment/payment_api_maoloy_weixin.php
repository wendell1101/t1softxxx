<?php
require_once dirname(__FILE__) . '/abstract_payment_api_maoloy.php';
/**
 * MAOLOY 仁信支付
 *
 * * MAOLOY_WEIXIN_PAYMENT_API, ID: 205
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://get.yichigo.com/online/gateway
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_maoloy_weixin extends Abstract_payment_api_maoloy {

	public function getPlatformCode() {
		return MAOLOY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'maoloy_weixin';
	}

	public function getBankType($direct_pay_extra_info){
		return $this->utils->is_mobile() ? parent::BANK_TYPE_WEIXIN_WAP : parent::BANK_TYPE_WEIXIN;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}