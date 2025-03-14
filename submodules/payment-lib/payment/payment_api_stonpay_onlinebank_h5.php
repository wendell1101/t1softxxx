<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stonpay.php';

/**
 *
 * * STONPAY_ONLINEBANK_H5_PAYMENT_API', ID: 5083
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
class Payment_api_stonpay_onlinebank_h5 extends Abstract_payment_api_stonpay {

    public function getPlatformCode() {
        return STONPAY_ONLINEBANK_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'stonpay_onlinebank_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['pType'] = self::PTYPE_ONLINEBANK;
        $params['bankCode'] = $bank;
        $params['bankAccountType'] = self::BANKACCOUNTTYPE;
        $params['mobile'] = '1'; //移动端（当为手机端时此参数不为空值为1
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
