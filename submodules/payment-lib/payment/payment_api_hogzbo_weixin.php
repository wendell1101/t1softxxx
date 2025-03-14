<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hogzbo.php';

/** 
 *
 * hogzbo  
 * 
 * 
 * * HOGZBO_WEIXIN_PAYMENT_API, ID: 741
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.hogzbo.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hogzbo_weixin extends Abstract_payment_api_hogzbo {

	public function getPlatformCode() {
		return HOGZBO_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hogzbo_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
       
            $params['payType'] = self::PAYTYPE_WEIXIN;
            
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {

            return $this->processPaymentUrlFormQRCode($params);   
		
	
	}
}
