<?php
require_once dirname(__FILE__) . '/abstract_payment_api_luckypay.php';

/** 
 *
 * LUCKYPAY
 * 
 * 
 * * 'LUCKYPAY_ALIPAY_PAYMENT_API', ID 5677
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.75.191.227:83/lucky/to-pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_luckypay_alipay extends Abstract_payment_api_luckypay {

	public function getPlatformCode() {
		return LUCKYPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'luckypay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['OrderType'] = self::PAYWAY_ALIPAY;
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
