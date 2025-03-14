<?php
require_once dirname(__FILE__) . '/abstract_payment_api_maoloy.php';
/**
 * MAOLOY 仁信支付
 *
 * * MAOLOY_ALIPAY_PAYMENT_API, ID: 204
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
class Payment_api_maoloy_alipay extends Abstract_payment_api_maoloy {

	public function getPlatformCode() {
		return MAOLOY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'maoloy_alipay';
	}

	public function getBankType($direct_pay_extra_info){
		return $this->utils->is_mobile() ? parent::BANK_TYPE_ALIPAY_WAP : parent::BANK_TYPE_ALIPAY;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}