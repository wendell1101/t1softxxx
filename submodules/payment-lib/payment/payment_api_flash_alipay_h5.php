<?php
require_once dirname(__FILE__) . '/abstract_payment_api_flash.php';

/**
 * FLASH  Flashpay
 * *
 * * FLASH_ALIPAY_H5_PAYMENT_API, ID: 5506
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://188.166.206.92:8080/payments/qr
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_flash_alipay_h5 extends Abstract_payment_api_flash {

    public function getPlatformCode() {
        return FLASH_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'flash_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        // $params['channel'] = self::CHANNEL_ALIPAY;
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
