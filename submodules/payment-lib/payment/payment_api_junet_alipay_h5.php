<?php
require_once dirname(__FILE__) . '/abstract_payment_api_junet.php';

/**
 *
 * * JUNET_ALIPAY_H5_PAYMENT_API', ID: 5340
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 
 * Field Values:
 * * URL: http://pay3.junet.tech:8080/pay/payment.api
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_junet_alipay_h5 extends Abstract_payment_api_junet {

    public function getPlatformCode() {
        return JUNET_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'junet_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
