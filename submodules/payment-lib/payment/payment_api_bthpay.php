<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bthpay.php';

/**
 * BTHPAY
 * http://office.bth.ph/login/
 *
 * * BTHPAY_PAYMENT_API, ID: 848
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_info: bthpay_sn_key
 *
 * Field Values:
 * * URL: http://apipay.bth.ph/pay/gateway
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra_info: { "bthpay_sn_key" : "## MERCHANT_SIGN_KEY_FOR_SN ##" }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_bthpay extends Abstract_payment_api_bthpay {

    public function getPlatformCode() {
        return BTHPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bthpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['type'] = self::PAY_TYPE_BANK;
    }

    # Config in extra_info will overwrite this
    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '北京银行', 'value' => 'BJBANK'),
            array('label' => '北京农商银行', 'value' => 'BJRCB'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '宁波银行', 'value' => 'NBBANK'),
            array('label' => '平安银行', 'value' => 'SPABANK'),
            array('label' => '华夏银行', 'value' => 'HXBANK'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            array('label' => '杭州银行', 'value' => 'HZCB'),
            array('label' => '交通银行', 'value' => 'COMM'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '广发银行', 'value' => 'GDB')
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}