<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vmpay.php';
/**
 * VMPAY
 *
 * * VMPAY_PAYMENT_API, ID: 5747
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tigerpayhub.com/apisubmit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vmpay extends Abstract_payment_api_vmpay {

    public function getPlatformCode() {
        return VMPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'vmpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::PAYTYPE_ONLINE_BANK;
        $params['bankcode'] = $this->getBankType($direct_pay_extra_info);
    }

    public function getBankType($direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        } else {
            return parent::getBankType($direct_pay_extra_info);
        }
    }

    # Config in extra_info will overwrite this
    public function getBankListInfoFallback() {
        return array(
            array('label' => '中國工商銀行', 'value' => 'ICBC'),
            array('label' => '中國農業銀行', 'value' => 'ABC'),
            array('label' => '中國銀行', 'value' => 'BOC'),
            array('label' => '中國建設銀行', 'value' => 'CCB'),
            array('label' => '交通銀行', 'value' => 'BOCOM'),
            array('label' => '中國光大銀行', 'value' => 'CEB'),
            array('label' => '上海浦東發展銀行', 'value' => 'SPDB'),
            array('label' => '北京銀行', 'value' => 'BCCB'),
            array('label' => '廣東發展銀行', 'value' => 'GDB'),
            array('label' => '平安銀行', 'value' => 'PAB'),
            array('label' => '興業銀行', 'value' => 'CIB'),
            array('label' => '招商銀行', 'value' => 'CMB'),
            array('label' => '中國郵政儲蓄銀行', 'value' => 'PSBC'),
            array('label' => '華夏銀行', 'value' => 'HXB'),
            array('label' => '民生銀行', 'value' => 'CMBC'),
            array('label' => '中信銀行', 'value' => 'ECITIC'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}