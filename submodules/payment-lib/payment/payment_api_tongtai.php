<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tongtai.php';
/**
 * TONGTAI 通泰
 *
 * * TONGTAI_PAYMENT_API, ID: 5132
 *
 * Required Fields:
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://69.172.75.141:7802/api.php/wgpay/wap_pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tongtai extends Abstract_payment_api_tongtai {

    public function getPlatformCode() {
        return TONGTAI_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tongtai';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['bankId'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
