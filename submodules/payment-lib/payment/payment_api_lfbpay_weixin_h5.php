<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lfbpay.php';

/**
 * LFBPAY 乐付宝 - 微信 H5
 * 
 *
 * LFBPAY_WEIXIN_H5_PAYMENT_API, ID: 531
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://trade.test.com/cooperate/gateway.cgi
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lfbpay_weixin_h5 extends Abstract_payment_api_lfbpay {

	public function getPlatformCode() {
		return LFBPAY_WEIXIN_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lfbpay_weixin_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['typeId'] = self::PAYTYPE_WEIXIN;

		if($this->getSystemInfo('real_h5')) {
			$params['service'] = self::SERVICE_H5PAY;			
		}
		else {
			$params['service'] = self::SERVICE_SCANPAY;		
		}	
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		if($this->getSystemInfo('real_h5')) {
			return $this->processPaymentUrlFormPost($params);
		}
		return $this->processPaymentUrlFormQRCode($params);
	}

}
