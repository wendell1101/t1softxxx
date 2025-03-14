<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cowboy.php';

/**
 *
 * * COWBOY_PAYMENT_API, ID: 5961
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://sfpay8.com/api/gateway/index.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cowboy extends Abstract_payment_api_cowboy {

	public function getPlatformCode() {
		return COWBOY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cowboy';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channel'] = self::PAY_METHOD_BANK_CARD;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}
