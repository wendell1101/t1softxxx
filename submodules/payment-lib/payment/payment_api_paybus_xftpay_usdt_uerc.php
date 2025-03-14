<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6449
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_xftpay_usdt_uerc extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_XFTPAY_USDT_UERC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_xftpay_usdt_uerc';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {   
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_XFTPAY_USDT => array(
                "type" => $this->getSystemInfo('channel_type')
            )
        ]));
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        $getPlayerInputInfo= array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}