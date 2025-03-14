<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nibpay.php';

/**
 * NIBPAY_NIBTRAN
 *
 * * NIBPAY_NIBTRAN_PAYMENT_API, ID: 6002
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.nibpay.com
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_nibpay_NIBTRAN extends Abstract_payment_api_nibpay {

    public function getPlatformCode() {
        return NIBPAY_NIBTRAN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nibpay_nibtran';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channels'] = self::DEPOSIT_CHANNEL_NIBTRAN;
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
