<?php
require_once dirname(__FILE__) . '/abstract_payment_api_atrustpay.php';

/** 
 *
 * ATRUSTPAY 信付宝
 * 
 * 
 * * ATRUSTPAY_WEIXIN_WAP_PAYMENT_API, ID: 478
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.atrustpay.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_atrustpay_weixin_wap extends Abstract_payment_api_atrustpay {

	public function getPlatformCode() {
		return ATRUSTPAY_WEIXIN_WAP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'atrustpay_weixin_wap';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		$params['receivableType'] = 'T01';   //D+0、T+1、D+1  ///uncertain
		$params['payMode'] = self::PAYMODE_WEXIN_WAP;
		$params['tranChannel'] = '103'; //uncertain
		
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormWeixinH5($params);
		
	}
}
