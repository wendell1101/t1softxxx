<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hanpays.php';
/**
 * HANPAYS
 *
 * * HANPAYS_PAYMENT_API, ID: 5795
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.hanpays.co/data/api/hanshi/receivables
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hanpays extends Abstract_payment_api_hanpays {

    public function getPlatformCode() {
        return HANPAYS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hanpays';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['paytype'] = self::PAYTYPE_ONLINEBANK;
        $params['bankcode'] = $bank;
        $params['bankname'] = $this->getBankName($bank);
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    private function getBankName($bankCode){
        $bankList = $this->getBankListInfo();
        foreach($bankList as $aBankArray){
            if(strtoupper($bankCode) == $aBankArray['value']) {
                return $aBankArray['label'];
            }
        }
    }
}