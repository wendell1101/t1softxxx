<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aeepay.php';

/**
 * lelipay
 *
 * * AEEPAY_BANKTRANSFER_PAYMENT_API, ID: 6301
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
class payment_api_aeepay_banktransfer extends Abstract_payment_api_aeepay {

    public function getPlatformCode() {
        return AEEPAY_BANKTRANSFER_PAYMENT_API;
    }

    public function getPrefix() {
        return 'aeepay_banktransfer';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paycode'] = self::TRANSFER_PAYMENT;
        return $params['paycode'] ;
    
    }

   

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}