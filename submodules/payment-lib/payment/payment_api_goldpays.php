<?php
require_once dirname(__FILE__) . '/abstract_payment_api_goldpays.php';
/**
 * goldpays
 *
 * * goldpays_ALIPAY_PAYMENT_API, ID: 5236
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.75.122.187:39318/mpay/index.php/goldpays/unifiedOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_goldpays extends Abstract_payment_api_goldpays {

    public function getPlatformCode() {
        return GOLDPAYS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'goldpays';
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
        return $this->processPaymentUrlFormURL($params);
    }
}