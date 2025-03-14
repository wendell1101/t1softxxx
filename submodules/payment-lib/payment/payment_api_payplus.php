<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payplus.php';

/**
 *
 * payplus
 *
 *
 * * 'payplus_PAYMENT_API', ID 6029
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payplus.cc/Apipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_payplus extends Abstract_payment_api_payplus {

	public function getPlatformCode() {
		return PAYPLUS_PAYMENT_API;
	}

	public function getPrefix() {
		return 'payplus';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::PAYWAY_BANK;
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
