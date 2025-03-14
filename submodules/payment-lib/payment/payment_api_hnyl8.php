<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hnyl8.php';

/**
 * HNYL8
 *
 * * HNYL8_PAYMENT_API, ID: 863
 *
 * Required Fields:
 * * URL
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: https://gateways.hnyl8.top/b2cPay/initPay
 * * Key: ## pay key ##
 * * Secret: ## pay secret ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hnyl8 extends Abstract_payment_api_hnyl8 {

    public function getPlatformCode() {
        return HNYL8_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hnyl8';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    # Config in extra_info will overwrite this
    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国邮政储蓄银行', 'value' => 'POST'),
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '上海银行', 'value' => 'SHB'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}