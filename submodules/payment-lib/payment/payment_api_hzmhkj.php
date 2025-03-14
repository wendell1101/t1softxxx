<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hzmhkj.php';

/** 
 *
 * HZMHKJ  嘉联支付
 * 
 * 
 * * HZMHKJ_QQPAY_PAYMENT_API, ID: 619
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
class Payment_api_hzmhkj extends Abstract_payment_api_hzmhkj {

	public function getPlatformCode() {
		return HZMHKJ_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hzmhkj';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
                $params['bankcode'] = $bank;
			}
        }
        

		$params['paytype'] = self::PAYTYPE_BANK;
		
	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}
}
