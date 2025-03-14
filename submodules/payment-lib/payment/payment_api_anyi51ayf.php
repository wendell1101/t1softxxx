<?php
require_once dirname(__FILE__) . '/abstract_payment_api_anyi51ayf.php';

/**
 *
 * * ANYI51AYF_PAYMENT_API', ID: 957
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
class Payment_api_anyi51ayf extends Abstract_payment_api_anyi51ayf {

    public function getPlatformCode() {
        return ANYI51AYF_PAYMENT_API;
    }

    public function getPrefix() {
        return 'anyi51ayf';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['payChannelCode'] = $bank;
        $params['payChannelType'] = '1';
        $params['orderSource'] = $this->CI->utils->is_mobile()? '2' :'1';
        $params['payType'] = self::PAYTYPE_ONLINEBANK;

    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
