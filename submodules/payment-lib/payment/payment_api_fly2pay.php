<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fly2pay.php';
/**
 * FLY2PAY
 *
 * * FLY2PAY_PAYMENT_API, ID: 5639
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://fly2pay.com/api/fundin/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fly2pay extends Abstract_payment_api_fly2pay {

    public function getPlatformCode() {
        return FLY2PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fly2pay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['deposit_method_id'] = '1';
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_id'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}