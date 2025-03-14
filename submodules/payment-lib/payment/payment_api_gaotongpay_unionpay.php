<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gaotongpay.php';
/**
 * GAOTONGPAY 高通/易收付
 *
 * * GAOTONGPAY_UNIONPAY_PAYMENT_API, ID: 387
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.ipsqs.com/PayBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gaotongpay_unionpay extends Abstract_payment_api_gaotongpay {

	public function getPlatformCode() {
		return GAOTONGPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gaotongpay_unionpay';
	}

	public function getBankType($direct_pay_extra_info){
		return $this->getSystemInfo("pc_banktype", parent::BANK_TYPE_UNIONPAY);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}