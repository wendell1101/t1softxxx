<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hzmhkj.php';

/** 
 *
 * HZMHKJ  嘉联支付
 * 
 * 
 * * HZMHKJ_UNIONPAY_PAYMENT_API, ID: 620
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.hzmhkj.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hzmhkj_unionpay extends Abstract_payment_api_hzmhkj {

	public function getPlatformCode() {
		return HZMHKJ_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hzmhkj_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        
			$params['paytype'] = self::PAYTYPE_UNIONPAY;
			$params['bankcode'] = '';
		
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
