<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aobangpay.php';
/**
 * aobangpay  奥邦
 *
 * * AOBANGPAY_PAYMENT_API, ID: 5048
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.aobang2pay.com/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aobangpay extends Abstract_payment_api_aobangpay {

    public function getPlatformCode() {
        return AOBANGPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'aobangpay';
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
        $params['product_type'] = self::PRODUCT_TYPE_ONLINEBANK;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
