<?php
require_once dirname(__FILE__) . '/abstract_payment_api_qbpay.php';
/**
 * QBPay
 *
 * * QBPAY_ALIPAY_H5_PAYMENT_API, ID: 5072
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * 
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qbpay_alipay_h5 extends Abstract_payment_api_qbpay {

    public function getPlatformCode() {
        return QBPAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'qbpay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['stype'] = self::STYPE_ALIPAY_H5;
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
