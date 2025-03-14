<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nyypay.php';

/**
 *
 * nyypay
 *
 *
 * * 'NYYPAY_MOMO_PAYMENT_API', ID 6124
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.nyypay77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_nyypay_momo extends Abstract_payment_api_nyypay {

    public function getPlatformCode() {
        return NYYPAY_MOMO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nyypay_momo';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params["method"] = self::PAYWAY_MOMO;
        $params['accountBank'] = 'MOMO';
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
