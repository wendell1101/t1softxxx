<?php
require_once dirname(__FILE__) . '/abstract_payment_api_allbank.php';

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
class Payment_api_allbank_qrph extends Abstract_payment_api_allbank {

	public function getPlatformCode() {
		return ALLBANK_QRPH_PAYMENT_API;
	}

	public function getPrefix() {
		return 'allbank_qrph';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['cmd'] = self::ALLBANK_QRPH;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlQRCode($params);
	}
	
	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
