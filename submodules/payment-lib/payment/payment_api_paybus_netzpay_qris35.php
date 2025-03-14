<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_NETZPAY_QRIS35_PAYMENT_API
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_netzpay_qris35 extends Abstract_payment_api_paybus {
    const CHANNEL_NETZPAY_QRIS35 = 35;
    public function getPlatformCode() {
        return PAYBUS_NETZPAY_QRIS35_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_netzpay_qris35';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['binding_id'] = self::CHANNEL_NETZPAY_QRIS35;
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_NETZPAY_QRIS => [
                'commissionPercentage' => $this->getSystemInfo('commissionPercentage', 0),
                'expireInSecond' => $this->getSystemInfo('expireInSecond', 600),
                'feeType' => $this->getSystemInfo('feeType', 'on_seller'),
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