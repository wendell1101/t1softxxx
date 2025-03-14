<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paylah88.php';

/**
 * paylah88
 * http://api.paylah88test.biz/MerchantTransfer
 *
 * * PAYLAH88_PAYMENT_API, ID: 5762
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.topasianpg.co/merchant/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paylah88 extends Abstract_payment_api_paylah88 {

    public function getPlatformCode() {
        return PAYLAH88_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paylah88';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['Bank'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
