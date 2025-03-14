<?php
require_once dirname(__FILE__) . '/abstract_payment_api_haohaopay.php';
/**
 * HAOHAOPAY 
 * 
 *
 * HAOHAOPAY_WEIXIN_PAYMENT_API, ID: 752
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.haohaopay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_haohaopay_weixin extends Abstract_payment_api_haohaopay {

	public function getPlatformCode() {
		return HAOHAOPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'haohaopay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
		if($this->CI->utils->is_mobile()) {
			$params['payType'] = self::DEFAULTNANK_WEIXIN_WAP;
		}else{
			$params['payType'] = self::DEFAULTNANK_WEIXIN;
		}
		
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		if($this->CI->utils->is_mobile()) {
			return $this->processPaymentUrlFormPost($params);
		}else{
			return $this->processPaymentUrlFormQRCode($params);
		}
	}

}
