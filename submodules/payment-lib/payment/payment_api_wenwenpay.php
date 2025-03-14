<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wenwenpay.php';

/**
 * WENWENPAY
 *
 * * WENWENPAY_PAYMENT_API, ID: 5975
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://web.jf3092.com/paygate/pay.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wenwenpay extends Abstract_payment_api_wenwenpay {

    public function getPlatformCode() {
        return WENWENPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wenwenpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::DEPOSIT_CHANNEL_BANK;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
