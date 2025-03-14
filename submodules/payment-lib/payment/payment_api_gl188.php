<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gl188.php';
/**
 * gl188
 * *
 * * gl188_PAYMENT_API, ID: 6071
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://or.7xinpy.com/pay/orderPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gl188 extends Abstract_payment_api_gl188 {

    public function getPlatformCode() {
        return GL188_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gl188';
    }

    public function getBankType($direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                 $this->CI->utils->debug_log('=====================gl188  extraInfo', $extraInfo);
                return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        } else {
            return parent::getBankType($direct_pay_extra_info);
        }
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['orderType'] = self::CODE_BANK_TYPE;
        $params['merchantBankID']  = $this->getBankType($direct_pay_extra_info);
        $params['bankName']  = $this->mapBankName($params['merchantBankID']);
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getBankListInfoFallback() {
        $url = $this->getSystemInfo('getbankUrl');
        $params = [];
        $response = $this->submitGetForm($url, $params);
        $result = json_decode($response,true);
        $this->CI->utils->debug_log('======================gl188 getBankListInfoFallback response', $response);
        if(isset($result['data']) && !empty($result['data'])){
            foreach ($result['data'] as $key => $value) {
                $banklist[$key]['label'] = $value['bankName'];
                $banklist[$key]['value'] = $value['id'];
            }
            $this->CI->utils->debug_log('======================gl188 getBankListInfoFallback banklist', $banklist);
            return $banklist;
        }
        else{
            $getbank_fail_msg = $this->getSystemInfo("getbank_fail_msg", "無可用銀行");
            return array(
                array('label' => $getbank_fail_msg, 'value' => ''),
            );
        }
    }

    public function mapBankName($id){
        if(empty($id)){
            return $this->getSystemInfo("getbank_fail_msg", "無可用銀行");
        }else{
            $banklist = $this->getBankListInfoFallback();
            if(is_array($banklist)){
                foreach ($banklist as $bank) {
                    foreach ($bank as $key => $value) {
                        if($key == 'value' && $value == $id){
                            return $bank['label'];
                        }
                    }
                }
            }else{
                return $this->getSystemInfo("getbank_fail_msg", "無可用銀行");
            }
        }
    }
}
