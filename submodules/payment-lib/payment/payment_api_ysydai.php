<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ysydai.php';

/**
 * YSYDAI
 *
 * * YSYDAI_PAYMENT_API, ID: 5263
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.ysydai.cn/pay/ap.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ysydai extends Abstract_payment_api_ysydai {

    public function getPlatformCode() {
        return YSYDAI_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ysydai';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = self::PAYTYPE_ONLINEBANK;
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function getBankListInfoFallback() {
        return array(
            array('label' => '中国邮政储蓄银行', 'value' => 'POST'),
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '上海银行', 'value' => 'SHB'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}