<?php
require_once dirname(__FILE__) . '/abstract_payment_api_s88pay.php';
/**
 * s88pay
 * *
 * * S88PAY_PAYMENT_API, ID: 6158
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://a.s88pay.im/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_s88pay extends Abstract_payment_api_s88pay {

    public function getPlatformCode() {
        return S88PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 's88pay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payment_code'] = self::BUSICODE_BANK;
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }

    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
