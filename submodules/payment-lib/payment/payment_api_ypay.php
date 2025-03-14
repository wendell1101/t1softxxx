<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ypay.php';

/**
 *
 * * YPAY_PAYMENT_API', ID: 6103
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: http://pay.wtzf168.com/v1/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ypay extends Abstract_payment_api_ypay {

    public function getPlatformCode() {
        return YPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ypay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['payType'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'BCA', 'value' => 'BCA'),
            array('label' => 'BRI', 'value' => 'BRI'),
            array('label' => 'BNI', 'value' => 'BNI'),
            array('label' => 'MANDIRI', 'value' => 'MANDIRI')
        );
    }
}
