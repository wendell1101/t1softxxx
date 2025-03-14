<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hupay.php';

/**
 * HUPAY 互匯
 * *
 * * HUPAY_PAYMENT_API, ID: 5389
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.hupay.com/api/v1/order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hupay extends Abstract_payment_api_hupay {

    public function getPlatformCode() {
        return HUPAY_PAYMENT_API;
    }

    public function getPrefix() { 
        return 'hupay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = self::PAYTYPE_ONLINEBANK;

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
