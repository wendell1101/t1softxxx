<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huitsaipay.php';
/**
 *   HUITSAIPAY
 *
 * * HUITSAIPAY_ALIPAY_PAYMENT_API, ID: 5775
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.hbpqn.cn/api/deposit/page
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huitsaipay_alipay extends Abstract_payment_api_huitsaipay {

    public function getPlatformCode() {
        return HUITSAIPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'huitsaipay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
       $params['paymentType'] = self::PAYMENT_TYPE_ALIPAY;
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
