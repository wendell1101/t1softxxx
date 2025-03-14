<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6560
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_larkpay_qris extends Abstract_payment_api_paybus {

    const CHANNEL_LARKPAY_QRIS = 'larkpay.QRIS';

    public function getPlatformCode() {
        return PAYBUS_LARKPAY_QRIS_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_larkpay_qris';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_LARKPAY_QRIS => array(
                "returnUrl" => $this->getSystemInfo('returnUrl')
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