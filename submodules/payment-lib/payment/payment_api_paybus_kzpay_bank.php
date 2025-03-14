<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_KZPAY_BANK_PAYMENT_API, ID: 6473
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_kzpay_bank extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_KZPAY_BANK_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_kzpay_bank';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $paymentCode = $this->getSystemInfo('paymentCode', '1019');
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_KZPAY_DEPOSIT => [
                'paymentMethod' => $paymentCode
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