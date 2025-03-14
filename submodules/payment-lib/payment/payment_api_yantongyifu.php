<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yantongyifu.php';

/**
 *
 * * YANTONGYIFU_PAYMENT_API', ID: 5066
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: http:// 212.64.89.203:8889/tran/cashier/pay.ac
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yantongyifu extends Abstract_payment_api_yantongyifu {

    public function getPlatformCode() {
        return YANTONGYIFU_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yantongyifu';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['bankCode'] = $bank;
        $params['tranType'] = self::TRANTYPE_ONLINEBANK;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
