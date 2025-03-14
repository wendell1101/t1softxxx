<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yongpay.php';
/**
 * YONGPAY 
 * 
 *
 * YONGPAY_UNIONPAY_PAYMENT_API, ID: 853
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.yongpay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yongpay_unionpay extends Abstract_payment_api_yongpay {

	public function getPlatformCode() {
		return YONGPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yongpay_unionpay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
		
		if($this->CI->utils->is_mobile()) {
			$params['pay_channelCode'] = self::DEFAULTNANK_UNIONPAY;
        	$params['isMobile'] = true;
		}
		else {
			$params['pay_channelCode'] = self::DEFAULTNANK_UNIONPAY;
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
