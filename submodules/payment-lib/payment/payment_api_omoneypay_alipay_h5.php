<?php
require_once dirname(__FILE__) . '/abstract_payment_api_omoneypay.php';

/**
 * OMONEYPAY
 * upay.omoneypay.com
 *
 * * OMONEYPAY_ALIPAY_H5_PAYMENT_API, ID: 5630
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://upay.omoneypay.com/cgi-bin/v2.0/unite_pay_apply.cgi
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_omoneypay_alipay_h5 extends Abstract_payment_api_omoneypay {

    public function getPlatformCode() {
        return OMONEYPAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'omoneypay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['out_channel'] = self::OUT_CHANNEL_ALIPAY;
        $params['pay_type'] = self::PAY_TYPE_H5;
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
