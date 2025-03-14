<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hadespay_qris.php';
/**
 *
 * * HADESPAY_QRIS_PAYMENT_API, ID: 6596
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ib.brazil-pix.com/open-api/pay/payment
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_hadespay_qris extends Abstract_payment_api_hadespay_qris {

    public function getPlatformCode() {
        return HADESPAY_QRIS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hadespay_qris';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    public function getPlayerInputInfo() {
        $getPlayerInputInfo =  array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );

        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}