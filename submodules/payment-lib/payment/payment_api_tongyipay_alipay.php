<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tongyipay.php';

/**
 *
 * * TONGYIPAY_ALIPAY_PAYMENT_API', ID: 5108
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
class Payment_api_tongyipay_alipay extends Abstract_payment_api_tongyipay {

    public function getPlatformCode() {
        return TONGYIPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tongyipay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['request']['tradeType'] = self::TRADE_TYPE_H5;
        $params['request']['payChannel'] = self::PAY_CHANNEL_ALIPAY;
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
