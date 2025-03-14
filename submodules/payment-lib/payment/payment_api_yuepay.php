<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yuepay.php';

/**
 *
 * * YUEPAY_PAYMENT_API,        ID: 718
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxin
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yuepay extends Abstract_payment_api_yuepay {

    public function getPlatformCode() {
        return YUEPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yuepay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['tunnelcode'] = self::TUNNEL_CODE_BANK;
        $params['payextraparams']['bankid'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrl($params);
    }
}
