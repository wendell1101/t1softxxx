<?php
require_once dirname(__FILE__) . '/abstract_payment_api_arcpay.php';

/**
 * ARCPAY 大强
 * *
 * * ARCPAY_PAYMENT_API, ID: 898
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.arcpay.info/gateway/payApi
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_arcpay extends Abstract_payment_api_arcpay {

    public function getPlatformCode() {
        return ARCPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'arcpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_ONLINEBANK;
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
