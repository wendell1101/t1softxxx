<?php
require_once dirname(__FILE__) . '/abstract_payment_api_metronic.php';

/**
 * METRONIC
 *
 * * METRONIC_PAYMENT_API, ID: 6290
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_metronic extends Abstract_payment_api_metronic {

    public function getPlatformCode() {
        return METRONIC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'metronic';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}