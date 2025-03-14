<?php
require_once dirname(__FILE__) . '/abstract_payment_api_duoduopay.php';
/**
 *DUODUOPAY
 *  
 * http://merchant.duoduopayment.com 
 * DUODUOPAY_QQPAY_PAYMENT_API, ID: 318
 *
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_duoduopay_qqpay extends Abstract_payment_api_duoduopay {

	public function getPlatformCode() {
		return DUODUOPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'duoduopay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['BankCode'] ='QQWALLET';
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
