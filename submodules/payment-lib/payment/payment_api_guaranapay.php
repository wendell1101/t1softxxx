<?php
require_once dirname(__FILE__) . '/abstract_payment_api_guaranapay.php';

/**
 *
 * guaranapay
 * *
 * *
 * * GUARANAPAY_PAYMENT_API, ID: 5992

 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.63405.com/mctrpc/order/mkReceiptOrder.htm
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_guaranapay extends Abstract_payment_api_guaranapay {

	public function getPlatformCode() {
		return GUARANAPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'guaranapay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pmId'] = self::PAYTYPE_CPF;
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
