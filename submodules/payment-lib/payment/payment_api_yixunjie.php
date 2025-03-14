<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yixunjie.php';
/**
 * yixunjie 易迅捷 / GUANSHIN 广鑫
 *
 * * YIXUNJIE_PAYMENT_API, ID: 5080
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.244.47.216/orderpay.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yixunjie extends Abstract_payment_api_yixunjie {

    public function getPlatformCode() {
        return YIXUNJIE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yixunjie';
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
        $params['tradeType'] = self::TRADETYPE_ONLINEBANK;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
