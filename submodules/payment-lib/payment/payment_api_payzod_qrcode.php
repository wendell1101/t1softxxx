<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payzod.php';

/**
 *
 * * PAYZOD_QRCODE_PAYMENT_API, ID: 5621
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://dev.payzod.com/api/qr/
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_payzod_qrcode extends Abstract_payment_api_payzod {

	public function getPlatformCode() {
		return PAYZOD_QRCODE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'payzod_qrcode';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['paytype'] = self::PAYTYPE;
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
