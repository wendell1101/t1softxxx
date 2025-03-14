<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_WWWPAGO_PIX_PAYMENT_API, ID: 6392
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_wwwpago_pix extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_WWWPAGO_PIX_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_wwwpago_pix';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_WWWPAGO_PIX => [
                "return_url" => $this->getSystemInfo('returnUrl'),
                "user_ip" => $this->CI->utils->getIP()
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