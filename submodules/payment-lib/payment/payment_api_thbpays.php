<?php
require_once dirname(__FILE__) . '/abstract_payment_api_thbpays.php';
/**
 * THBPAYS
 *
 * * THBPAYS_PAYMENT_API, ID:
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://thbpays.com/api/fundin/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_thbpays extends Abstract_payment_api_thbpays {

    public function getPlatformCode() {
        return THBPAYS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'thbpays';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['deposit_method_id'] = "1";
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
