<?php
require_once dirname(__FILE__) . '/abstract_payment_api_allbank_instapay.php';

/**
 * ALLBANK
 * 
 *
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_allbank_instapay extends Abstract_payment_api_allbank_instapay {

	public function getPlatformCode() {
		return ALLBANK_INSTAPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'allbank_instapay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['cmd'] = self::ALLBANK_INSTAPAY;
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
