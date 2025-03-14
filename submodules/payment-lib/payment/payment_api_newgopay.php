<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newgopay.php';

/**
 * NEWGOPAY
 * *
 * * NEWGOPAY_PAYMENT_API, ID: 6016
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://drt1iji2j13.gopay001.com/createpay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_newgopay extends Abstract_payment_api_newgopay {

    public function getPlatformCode() {
        return NEWGOPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'NEWGOPAY';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }

    public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
