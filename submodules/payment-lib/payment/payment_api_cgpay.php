<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cgpay.php';

/**
 *
 * cgpay
 *
 *
 * * 'cgpay_PAYMENT_API', ID 6035
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://public.cgpay.io/api/v3/CreateCGPPayOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cgpay extends Abstract_payment_api_cgpay {

	public function getPlatformCode() {
		return CGPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cgpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['OrderTimeLive'] ='900';
		$params['Symbol'] = self::PAYWAY_CGP;
		$params['ReferUrl'] = $this->getReturnUrl($params['temp_orderId']);
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
