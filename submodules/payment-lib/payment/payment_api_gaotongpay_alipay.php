<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gaotongpay.php';
/**
 * GAOTONGPAY 高通/易收付
 *
 * * GAOTONGPAY_ALIPAY_PAYMENT_API, ID: 370
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
class Payment_api_gaotongpay_alipay extends Abstract_payment_api_gaotongpay {

	public function getPlatformCode() {
		return GAOTONGPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gaotongpay_alipay';
	}

	public function getBankType($direct_pay_extra_info){
		if($this->utils->is_mobile()){
			return $this->getSystemInfo("mobile_banktype", parent::BANK_TYPE_ALIPAY_WAP);
		}
		else{
			return $this->getSystemInfo("pc_banktype", parent::BANK_TYPE_ALIPAY);
		}
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}