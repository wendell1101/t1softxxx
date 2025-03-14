<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jiutongpay.php';
/**
 *
 * JIUTONGPAY 久通支付
 *
 * * JIUTONGPAY_PAYMENT_API, ID: 640
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.jiutongpay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jiutongpay extends Abstract_payment_api_jiutongpay {

    public function getPlatformCode() {
        return JIUTONGPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'jiutongpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['netway'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
