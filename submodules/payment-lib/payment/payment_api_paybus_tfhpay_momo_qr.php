<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_TFHPAY_MOMO_QR_PAYMENT_API, ID: 6610
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_tfhpay_momo_qr extends Abstract_payment_api_paybus {

    const CHANNEL_TFHPAY_MOMO_QR = 'tfhpay.momo_qr';

    public function getPlatformCode() {
        return PAYBUS_TFHPAY_MOMO_QR_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_tfhpay_momo_qr';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_TFHPAY_MOMO_QR => null
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