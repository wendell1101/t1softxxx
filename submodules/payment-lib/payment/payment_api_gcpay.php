<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gcpay.php';

/**
 * gcpay
 * *
 * * gcpay_PAYMENT_API, ID: 6016
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
class Payment_api_gcpay extends Abstract_payment_api_gcpay {

    public function getPlatformCode() {
        return GCPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gcpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $params['channel_code'] = self::ONLINEBANK_CHANNEL;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }

    public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
