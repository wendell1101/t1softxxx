<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paymero.php';
/**
 * PAYMERO
 *
 * * PAYMERO_PAYMENT_API, ID: 5718
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL:
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paymero extends Abstract_payment_api_paymero {

    public function getPlatformCode() {
        return PAYMERO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paymero';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['deviceType']     = 'WEB';
        $params['subIssuingBank'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
