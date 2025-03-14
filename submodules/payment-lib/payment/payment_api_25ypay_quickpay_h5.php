<?php
require_once dirname(__FILE__) . '/abstract_payment_api_25ypay.php';

/** 
 *
 * 25YPAY
 * 
 * 
 * * '_25YPAY_QUICKPAY_H5_PAYMENT_API', ID 5406
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.25ypay.cn/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_25ypay_quickpay_h5 extends Abstract_payment_api_25ypay {

	public function getPlatformCode() {
		return _25YPAY_QUICKPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return '25ypay_quickpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_QUICKPAY;
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {

        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
