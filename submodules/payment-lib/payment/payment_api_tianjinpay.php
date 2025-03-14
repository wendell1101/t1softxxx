<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianjinpay.php';

/**
 *  天津支付 罔銀
 * * TIANJINPAY_PAYMENT_API', ID: 5034 
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
class Payment_api_tianjinpay extends Abstract_payment_api_tianjinpay {

    public function getPlatformCode() {
        return TIANJINPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tianjinpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['TRANDATA']['BANKID']= $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
