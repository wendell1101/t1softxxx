<?php
require_once dirname(__FILE__) . '/abstract_payment_api_asiapay.php';

/**
 * ASIAPAY 亚付
 * *
 * * ASIAPAY_JDPAY_PAYMENT_API, ID: 892
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.asiapaycenter.com/gateway.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_asiapay_jdpay extends Abstract_payment_api_asiapay {

    public function getPlatformCode() {
        return ASIAPAY_JDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'asiapay_jdpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['trade_type'] = self::TRADE_TYPE_JDPAY_H5;
        }
        else {
            $params['trade_type'] = self::TRADE_TYPE_JDPAY;
        }
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
