<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kjfpay.php';

/**
 * KJFPAY 快捷付
 * *
 * * KJFPAY_PAYMENT_API, ID: 937
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://kjfpay.seepay.net/serviceDirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kjfpay extends Abstract_payment_api_kjfpay {

    public function getPlatformCode() {
        return KJFPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kjfpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payMethod'] = self::PAYMETHOD_ONLINEBANK;
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
