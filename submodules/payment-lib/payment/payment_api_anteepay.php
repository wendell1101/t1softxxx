<?php
require_once dirname(__FILE__) . '/abstract_payment_api_anteepay.php';

/**
 *
 * anteepay
 *
 * * ANTEEPAY_PAYMENT_API, ID: 6277
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_anteepay extends Abstract_payment_api_anteepay {

	public function getPlatformCode() {
		return ANTEEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'anteepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
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
