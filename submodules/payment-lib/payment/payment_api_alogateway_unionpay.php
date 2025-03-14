<?php
require_once dirname(__FILE__) . '/abstract_payment_api_alogateway.php';
/**
 * ALOGATEWAY
 *
 * * ALOGATEWAY_UNIONPAY_PAYMENT_API, ID: 996
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payment.cdc.alogateway.co/ChinaDebitCard
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_alogateway_unionpay extends Abstract_payment_api_alogateway {

    public function getPlatformCode() {
        return ALOGATEWAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'alogateway_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['bankcode'] = self::BANKCODE_UNIONPAY;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
