<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payone.php';

/**
 * payone
 *
 * * PAYONE_PAYMENT_API, ID: 6311
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.payone1.com/br/payment.json
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_payone extends Abstract_payment_api_payone {

    public function getPlatformCode() {
        return PAYONE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'payone';
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