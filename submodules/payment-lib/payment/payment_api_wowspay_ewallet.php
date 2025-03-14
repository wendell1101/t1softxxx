<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wowspay.php';

/**
 * wowspay
 *
 * * WOWSPAY_EWALLET_PAYMENT_API, ID: 6348
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_wowspay_ewallet extends Abstract_payment_api_wowspay {

    public function getPlatformCode() {
        return WOWSPAY_EWALLET_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wowspay_ewallet ';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);

        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }

        $params['paytype']    = self::MODE_EWALLET;
        $params['bankflag']   = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'GOPAY', 'value' => 'GOPAY'),
            array('label' => 'QRIS', 'value' => 'QRIS'),
            array('label' => 'DANA', 'value' => 'DANA'),
            array('label' => 'OVO', 'value' => 'OVO'),
            array('label' => 'SHOPEE PAY', 'value' => 'SHOPEE PAY'),
            array('label' => 'LINKAJA', 'value' => 'LINKAJA'),           
        );
    }
}