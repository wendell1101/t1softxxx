<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay4wallet.php';
/**
 * pay4wallet
 *
 * * PAY4WALLET_PAYMENT_API, ID: 6070
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.p4f.com/1.0/go/process
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay4wallet extends Abstract_payment_api_pay4wallet {

	public function getPlatformCode() {
		return PAY4WALLET_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pay4wallet';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

	}

	# Hide bank selection drop-down
    public function getPlayerInputInfo()
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
	}
}