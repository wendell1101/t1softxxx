<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eighteenpay.php';
/**
 * 18pay eighteenpay 卡轉卡
 *
 * * EIGHTEENPAY_PAYMENT_API, ID: 5862
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://18-pays.com/api/trans/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_eighteenpay extends Abstract_payment_api_eighteenpay {

    public function getPlatformCode() {
        return EIGHTEENPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'eighteenpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        // $params['pay_type'] = $this->getSystemInfo('bank_code',self::PAY_TYPE_ALIPAY);
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}