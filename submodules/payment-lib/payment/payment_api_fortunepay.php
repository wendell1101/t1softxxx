<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fortunepay.php';

/**
 * fortunepay
 *
 * * fortunepay_payment_api, ID: 6537
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
class Payment_api_fortunepay extends Abstract_payment_api_fortunepay {

    public function getPlatformCode() {
        return FORTUNEPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fortunepay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo(){
        return [
            [
                'name' => 'deposit_amount', 
                'type' => 'float_amount', 
                'label_lang' => 'cashier.09'
            ]
        ];
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}