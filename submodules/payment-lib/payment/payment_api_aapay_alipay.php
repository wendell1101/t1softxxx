<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aapay.php';
/**
 *   AAPAY
 *
 * * AAPAY_ALIPAY_PAYMENT_API, ID: 5529
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.tcpay.info/diamond/html/buy_opt.html
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aapay_alipay extends Abstract_payment_api_aapay {

    public function getPlatformCode() {
        return AAPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'aapay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
       $params['payType'] = self::PAYTYPE_ALIPAY;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
