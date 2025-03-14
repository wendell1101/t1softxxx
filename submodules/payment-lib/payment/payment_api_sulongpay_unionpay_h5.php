<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sulongpay.php';
/**
 * SULONGPAY 
 *
 * * SULONGPAY_UNIONPAY_H5_PAYMENT_API,  ID: 5278
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 *
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##

 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sulongpay_unionpay_h5 extends Abstract_payment_api_sulongpay {

	public function getPlatformCode() {
		return SULONGPAY_UNIONPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sulongpay_unionpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
            $params['trade_type'] = self::PAYTYPE_UNIONPAY;
            $params['buyername'] = 'Buyer';
            $params['subject'] = 'Deposit';
            $params['client_ip'] = $this->getClientIp();
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
		// return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
