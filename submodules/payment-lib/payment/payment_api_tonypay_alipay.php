<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tonypay.php';
/**
 * TONYPAY
 * 
 *
 * TONYPAY_ALIPAY_PAYMENT_API, ID: 5509
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.52.206.188:8025/apigw/service.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tonypay_alipay extends Abstract_payment_api_tonypay {

	public function getPlatformCode() {
		return TONYPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tonypay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
			$params['pay_type'] = self::PAYTYPE_ALIPAY_H5;
            $params['service'] = self::SERVICETYPE_H5;
        }
        else {
			$params['pay_type'] = self::PAYTYPE_ALIPAY;
			$params['service'] = self::SERVICETYPE_SCAN;
        }	
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
