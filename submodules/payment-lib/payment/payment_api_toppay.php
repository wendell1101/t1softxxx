<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pix_toppay.php';

/**
 * lelipay
 *
 * * TOPPAY_PAYMENT_API, ID: 6282
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
class Payment_api_toppay extends Abstract_payment_api_pix_toppay {

    public function getPlatformCode() {
        return TOPPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'toppay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::DESCRIPT;
        return $params['type'] ;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}