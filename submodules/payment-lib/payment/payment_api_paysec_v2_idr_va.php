<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paysec_v2.php';
/**
 * PAYSEC_V2
 *
 * * PAYSEC_IDR_VA_PAYMENT_API, ID: 631
 * *
 * Required Fields:
 * * Account
 * * Secret
 * * URL
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Secret: ## Merchant Key ##
 * * URL: https://payment.allpay.site/api/transfer/v1/payIn/sendTokenForm
 * * TOKEN URL: https://payment.allpay.site/api/transfer/v1/payIn/requestToken
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paysec_v2_idr_va extends Abstract_payment_api_paysec_v2 {

    public function getPlatformCode() {
        return PAYSEC_IDR_VA_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paysec_v2_idr_va';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channelCode'] = self::CHANNEL_BANKTRANSFER;
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'May Bank', 'value' => 'VA'),
            array('label' => 'Permata Bank', 'value' => 'VA_PER'),
            array('label' => 'BRI Bank', 'value' => 'VA_BNI'),
        );
    }
}
