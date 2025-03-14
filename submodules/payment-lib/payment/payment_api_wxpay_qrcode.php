<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wxpay.php';
/**
 * wxpay
 * *
 * * WXPAY_QRCODE_PAYMENT_API, ID: 6006
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://a.wxpay.im/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wxpay_qrcode extends Abstract_payment_api_wxpay {

    public function getPlatformCode() {
        return WXPAY_QRCODE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wxpay_qrcode';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::BUSICODE_QRCODE;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
