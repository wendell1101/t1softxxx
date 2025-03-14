<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aipay.php';

/**
 *
 * * AIPAY_PAYMENT_API', ID: 5037
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
class Payment_api_aipay extends Abstract_payment_api_aipay {

    public function getPlatformCode() {
        return AIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'aipay';
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
        $params['pay_mode'] = self::PAY_MODE_WEB;
    }

    protected function processPaymentUrlForm($params) {
        if($this->getSystemInfo("use_urlFormPost")){
            return $this->processPaymentUrlFormPost($params);

        }else{
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
