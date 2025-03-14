<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huitsaipay.php';
/**
 *   MTPAY
 *
 * * MTPAY_PAYMENT_API, ID:5968
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://https://merchant-api.rgroiho.cn/api/deposit/page
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mtpay extends Abstract_payment_api_huitsaipay {

    public function getPlatformCode() {
        return MTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'mtpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
       $params['paymentType'] = self::PAYMENT_TYPE_BANKCARD;
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
