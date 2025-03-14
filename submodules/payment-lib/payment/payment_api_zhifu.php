<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zhifu.php';
/**
 * ZHIFU 知付
 *
 * * ZHIFU_PAYMENT_API, ID:
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.aido88.cn/api_deposit.shtml
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zhifu extends Abstract_payment_api_zhifu {

    public function getPlatformCode() {
        return ZHIFU_PAYMENT_API;
    }

    public function getPrefix() {
        return 'zhifu';
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '中国光大银行', 'value' => 'CEBB'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '平安银行', 'value' => 'PINGAN'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
        );
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['Mode'] = self::MODE_ONLINEBANK;
        $params['BankCode'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
