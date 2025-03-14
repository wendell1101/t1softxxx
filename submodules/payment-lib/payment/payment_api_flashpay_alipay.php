<?php
require_once dirname(__FILE__) . '/abstract_payment_api_flashpay.php';

/**
 * FlashPay 闪付/随意付
 *
 * * FLASHPAY_ALIPAY_PAYMENT_API, ID: 109
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.easyipay.com/interface/AutoBank/index.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_flashpay_alipay extends Abstract_payment_api_flashpay {

	public function getPlatformCode() {
		return FLASHPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'flashpay_alipay';
	}

	public function getBankType($direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            return self::TYPE_ALIPAY_H5;
        }else{
            return self::TYPE_ALIPAY;
        }
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
