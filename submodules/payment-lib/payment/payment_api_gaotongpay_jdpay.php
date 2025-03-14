<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gaotongpay.php';
/**
 * GAOTONGPAY 高通/易收付
 *
 * * GAOTONGPAY_JDPAY_PAYMENT_API, ID: 373
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
class Payment_api_gaotongpay_jdpay extends Abstract_payment_api_gaotongpay {

	public function getPlatformCode() {
		return GAOTONGPAY_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gaotongpay_jdpay';
	}

	public function getBankType($direct_pay_extra_info){
		if($this->utils->is_mobile()){
			return $this->getSystemInfo("mobile_banktype", parent::BANK_TYPE_JDPAY_WAP);
		}
		else{
			return $this->getSystemInfo("pc_banktype", parent::BANK_TYPE_JDPAY);
		}
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}