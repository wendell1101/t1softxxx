<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yingsheng.php';
/**
 * YINGSHENG 盈盛
 *
 * * YINGSHENG_PAYMENT_API, ID: 5149
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: https://api.yspay365.com/rsa/deposit
 * * Extra Info:
 * > {
 * >    "yingsheng_priv_key": "## Private Key ##",
 * >    "yingsheng_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yingsheng extends Abstract_payment_api_yingsheng {

    public function getPlatformCode() {
        return YINGSHENG_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yingsheng';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['service_type'] = self::SERVICETYPE_ONLINEBANK;
    }

    protected function processPaymentUrlForm($params, $secure_id) {
        return $this->processPaymentUrlFormPost($params, $secure_id);
    }
}
