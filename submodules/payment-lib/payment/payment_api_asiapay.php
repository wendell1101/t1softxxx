<?php
require_once dirname(__FILE__) . '/abstract_payment_api_asiapay.php';

/**
 * ASIAPAY 亚付
 * *
 * * ASIAPAY_PAYMENT_API, ID: 891
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.asiapaycenter.com/gateway.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_asiapay extends Abstract_payment_api_asiapay {

    public function getPlatformCode() {
        return ASIAPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'asiapay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['trade_type'] = self::TRADE_TYPE_ONLINEBANK;
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
