<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kolapay.php';

/**
 *
 * kola
 *
 *
 * * 'KOLAPAY_ZALO_PAYMENT_API', ID 6144
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.kola77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kolapay_zalo extends Abstract_payment_api_kolapay {

    public function getPlatformCode() {
        return KOLAPAY_ZALO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kolapay_zalo';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params["payMethod"] = self::PAYWAY_ZALO;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
