<?php
require_once dirname(__FILE__) . '/abstract_payment_api_macaubus.php';

/**
 *
 * * MACAUBUS_PAYMENT_API', ID: 790
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 
 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_macaubus extends Abstract_payment_api_macaubus {

    public function getPlatformCode() {
        return MACAUBUS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'macaubus';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['pay_code'] = $bank;
        
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
