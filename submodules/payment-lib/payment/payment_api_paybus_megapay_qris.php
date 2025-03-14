<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_MEGAPAY_QRIS_PAYMENT_API, ID: 6457
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_megapay_qris extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_MEGAPAY_QRIS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_megapay_qris';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_MEGAPAY_QRIS => []
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