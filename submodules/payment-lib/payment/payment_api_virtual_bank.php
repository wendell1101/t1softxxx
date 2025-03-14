<?php
require_once dirname(__FILE__) . '/abstract_payment_api_virtual_bank.php';

/**
 *
 * VIRTUAL BANK
 * *
 * *
 * * VIRTUAL_BANK_PAYMENT_API, ID: 6609

 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://qris.otomatis.vip/api/generate
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_virtual_bank extends Abstract_payment_api_virtual_bank {

	public function getPlatformCode() {
		return VIRTUAL_BANK_PAYMENT_API;
	}

	public function getPrefix() {
		return 'virtual_bank';
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
