<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jspay.php';
/**
 * JSPAY 金顺支付
 *
 * * JSPAY_PAYMENT_API, ID: 5165
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: http://js.011vip.cn:9090/jspay/payGateway.htm
 * * Extra Info:
 * > {
 * >    "jspay_priv_key": "## Private Key ##",
 * >    "jspay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jspay extends Abstract_payment_api_jspay {

    public function getPlatformCode() {
        return JSPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'jspay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankId'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
