<?php
require_once dirname(__FILE__) . '/abstract_payment_api_allbank_pesonet.php';

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
class Payment_api_allbank_pesonet extends Abstract_payment_api_allbank_pesonet {

	public function getPlatformCode() {
		return ALLBANK_PESONET_PAYMENT_API;
	}

	public function getPrefix() {
		return 'allbank_pesonet';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['cmd'] = self::ALLBANK_PESONET;
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
