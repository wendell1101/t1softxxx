<?php
require_once dirname(__FILE__) . '/abstract_payment_api_duoypay.php';
/**
 * DUOYPAY 铎亿支付 - 支付寶
 * 
 *
 * DUOYPAY_ALIPAY_PAYMENT_API, ID: 643
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
class Payment_api_duoypay_alipay extends Abstract_payment_api_duoypay {

	public function getPlatformCode() {
		return DUOYPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'duoypay_alipay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
			if($this->CI->utils->is_mobile()) {
                $params['type'] = self::PAYTYPE_ALIPAY_WAP;
			}
			else {
                $params['type'] = self::PAYTYPE_ALIPAY;
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
