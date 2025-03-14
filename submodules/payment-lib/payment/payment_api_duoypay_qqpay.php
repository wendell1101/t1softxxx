<?php
require_once dirname(__FILE__) . '/abstract_payment_api_duoypay.php';
/**
 * DUOYPAY 铎亿支付 - 微信
 * 
 *
 * DUOYPAY_QQPAY_PAYMENT_API, ID: 644
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.duoypay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_duoypay_qqpay extends Abstract_payment_api_duoypay {

	public function getPlatformCode() {
		return DUOYPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'duoypay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['type'] = self::PAYTYPE_QQPAY_WAP;
        }
        else {
            $params['type'] = self::PAYTYPE_QQPAY;
        }	
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
