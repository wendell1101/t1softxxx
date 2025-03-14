<?php
require_once dirname(__FILE__) . '/abstract_payment_api_neweasypay.php';

/**
 * neweasypay
 *
 * * NEWEASYPAY_PAYMENT_API, ID: 5995
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.easypay999.com/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_neweasypay extends Abstract_payment_api_neweasypay {

    public function getPlatformCode() {
        return NEWEASYPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'neweasypay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = self::CHANNEL_TYPE_UPI;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}