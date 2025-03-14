<?php
require_once dirname(__FILE__) . '/abstract_payment_api_luxpag.php';
/**
 * luxpag
 *
 * * LUXPAG_CASH_PAYMENT_API, ID: 5932
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://developer.luxpag.com/cn/reference/checkout-redirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_luxpag_cash extends Abstract_payment_api_luxpag {

	public function getPlatformCode() {
		return LUXPAG_CASH_PAYMENT_API;
	}

	public function getPrefix() {
		return 'luxpag_cash';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['method'] = self::CHANNEL_CASH;
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