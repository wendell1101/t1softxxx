<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wepay.php';

/**
 *
 * * WEPAY_ALIPAY_PAYMENT_API', ID: 5119
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
class Payment_api_wepay_alipay extends Abstract_payment_api_wepay {

    public function getPlatformCode() {
        return WEPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wepay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['PayChannel'] = self::PAY_CHANNEL_ALIPAY;
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
