<?php
require_once dirname(__FILE__) . '/abstract_payment_api_beamo.php';
/**
 * beamo
 *
 * * BEAMO_CREDITCARD_PAYMENT_API, ID: 6229
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://developer.beamo.com/cn/reference/checkout-redirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_beamo_creditcard extends Abstract_payment_api_beamo {

	public function getPlatformCode() {
		return BEAMO_CREDITCARD_PAYMENT_API;
	}

	public function getPrefix() {
		return 'beamo_creditcard';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['enabledPaymentMethods'] = self::DEPOSIT_CRYPTO_CREDIT_CARD_TYPE;
	}

	# Hide bank selection drop-down
    public function getPlayerInputInfo(){
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
	}
}