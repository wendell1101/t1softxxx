<?php
require_once dirname(__FILE__) . '/abstract_payment_api_moneypayxi33.php';

/**
 *
 * * MONEYPAYXI33_ALIPAY_H5_PAYMENT_API', ID: 5059
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 
 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_moneypayxi33_alipay_h5 extends Abstract_payment_api_moneypayxi33 {

    public function getPlatformCode() {
        return MONEYPAYXI33_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'moneypayxi33_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['terminal'] = '1';
        $params['channel_code'] = self::CHANNEL_CODE_ALIPAY;
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
