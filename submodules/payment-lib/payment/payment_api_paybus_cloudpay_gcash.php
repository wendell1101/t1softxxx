<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 63
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_cloudpay_gcash extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_CLOUDPAY_GCASH_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_cloudpay_gcash';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_CLOUDPAY_GASH => [
                "return_url" => $this->getSystemInfo('returnUrl'),
                "payment_type" => $this->getSystemInfo('payment_type'),
            ]
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