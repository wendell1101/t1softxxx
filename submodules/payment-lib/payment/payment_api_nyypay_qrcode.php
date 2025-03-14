<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nyypay.php';

/**
 *
 * nyypay
 *
 *
 * * 'NYYPAY_QRCODE_PAYMENT_API', ID 6123
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.nyypay77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_nyypay_qrcode extends Abstract_payment_api_nyypay {

    public function getPlatformCode() {
        return NYYPAY_QRCODE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nyypay_qrcode';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params["method"] = self::PAYWAY_QRCODE;
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['accountBank'] = $extraInfo['bank'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}
