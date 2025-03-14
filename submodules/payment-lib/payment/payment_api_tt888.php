<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tt888.php';

/**
 *
 * * TT888_PAYMENT_API, ID: 5966
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
class Payment_api_tt888 extends Abstract_payment_api_tt888 {

	public function getPlatformCode() {
		return TT888_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tt888';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_bankcode'] = self::PAY_METHOD_BANK_CARD;
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
