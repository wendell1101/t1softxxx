<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ginspay.php';

/**
 * Ginspay 隱聯支付 網銀
 * * GINSPAY_PAYMENT_API', ID: 947
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ginspay extends Abstract_payment_api_ginspay {

    public function getPlatformCode() {
        return GINSPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ginspay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['bank_code'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
