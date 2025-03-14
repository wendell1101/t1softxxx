<?php
require_once dirname(__FILE__) . '/abstract_payment_api_alogateway.php';
/**
 * ALOGATEWAY
 *
 * * ALOGATEWAY_PAYMENT_API, ID: 994
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
class Payment_api_alogateway extends Abstract_payment_api_alogateway {

    public function getPlatformCode() {
        return ALOGATEWAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'alogateway';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankcode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
