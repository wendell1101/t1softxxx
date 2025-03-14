<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wealthpay.php';

/**
 * WEALTHPAY
 * http://merchant.topasianpg.co
 *
 * * WEALTHPAY_PAYMENT_API, ID: 5309
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.topasianpg.co/merchant/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wealthpay extends Abstract_payment_api_wealthpay {

    public function getPlatformCode() {
        return WEALTHPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wealthpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['BankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
