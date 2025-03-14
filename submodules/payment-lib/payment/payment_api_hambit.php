<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hambit.php';

/**
 * lelipay
 *
 * * HAMBIT_PAYMENT_API, ID: 6315
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
class Payment_api_hambit extends Abstract_payment_api_hambit {

    public function getPlatformCode() {
        return HAMBIT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hambit';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channelType'] = "PIX";
        $params['inputCpf'] = 0;
    }

   

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
        
    }
}