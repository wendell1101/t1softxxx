<?php
require_once dirname(__FILE__) . '/abstract_payment_api_qpay.php';
/**
 * QPay-微信
 * 
 *
 * * QPAY_WEIXIN_PAYMENT_API, ID: 561
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:https://www.qpayapi.com/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qpay_weixin extends Abstract_payment_api_qpay {

	public function getPlatformCode() {
		return QPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'qpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        
        $params['istype'] = self::P_CHANNEL_WEIXIN;
        	
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
