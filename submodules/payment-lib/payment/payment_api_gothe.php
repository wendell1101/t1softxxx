<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gothe.php';

/**
 *
 * gothe
 *
 *
 * * 'GOTHE_PAYMENT_API', ID 6035
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gothe.cc/Apipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gothe extends Abstract_payment_api_gothe {

	public function getPlatformCode() {
		return GOTHE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gothe';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channel_code'] = self::PAYWAY_BANK;
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
