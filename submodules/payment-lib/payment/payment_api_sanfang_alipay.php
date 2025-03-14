<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sanfang.php';
/**
 * SANFANG
 *
 * * SANFANG_ALIPAY_PAYMENT_API, ID: 5732
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://118.31.15.233:3020/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sanfang_alipay extends Abstract_payment_api_sanfang {

    public function getPlatformCode() {
        return SANFANG_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sanfang_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = self::PAYTYPE_ALIPAY;
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