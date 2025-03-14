<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6631
 *
 * Field Values:
 * * URL: https://stg-open.paybus.io/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_ptypay_promptpay extends Abstract_payment_api_paybus {

    const CHANNEL_PTYPAY_PROMPTPAY = 'ptypay.promptpay';
    
    public function getPlatformCode() {
        return PAYBUS_PTYPAY_PROMPTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_ptypay_promptpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_PTYPAY_PROMPTPAY => array(
            )
        ]));
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
    
}