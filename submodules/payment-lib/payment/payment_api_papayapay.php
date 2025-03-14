<?php
require_once dirname(__FILE__) . '/abstract_payment_api_papayapay.php';

/**
 * papayapay
 * *
 * * papayapay_PAYMENT_API, ID: 6060
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://scb-staging.xyzonline.app/api/v1/create-qr
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_papayapay extends Abstract_payment_api_papayapay {

    public function getPlatformCode() {
        return PAPAYAPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'papayapay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
