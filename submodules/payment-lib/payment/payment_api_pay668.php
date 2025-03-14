<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay668.php';
/**
 *
 * * pay668_PAYMENT_API, ID: 6349
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://portal.hkdintlpay.com
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay668 extends Abstract_payment_api_pay668 {
    public function getPlatformCode() {
        return PAY668_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pay668';
    }
    
    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}