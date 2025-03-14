<?php
require_once dirname(__FILE__) . '/abstract_payment_api_worldpay.php';

/**
 * worldpay
 *
 * * WORLDPAY_PAYMENT_API, ID: 6233
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mio.oceanp168.com/api/createOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_worldpay extends Abstract_payment_api_worldpay {

    public function getPlatformCode() {
        return WORLDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'worldpay';
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