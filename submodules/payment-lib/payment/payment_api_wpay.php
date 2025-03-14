<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wpay.php';

/**
 * lelipay
 *
 * * WPAY_PAYMENT_API, ID: 6266
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
 * @copyright 2013-2023 tot
 */
class Payment_api_wpay extends Abstract_payment_api_wpay {

    public function getPlatformCode() {
        return WPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::W_TYPE;
        return $params['type'] ;
    
    }

   

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}