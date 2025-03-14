<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tongtai.php';
/**
 * TONGTAI 通泰
 *
 * * TONGTAI_QUICKPAY_PAYMENT_API, ID: 5133
 *
 * Required Fields:
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://69.172.75.141:7802/api.php/wgpay/wap_pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tongtai_quickpay extends Abstract_payment_api_tongtai {

    public function getPlatformCode() {
        return TONGTAI_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tongtai_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
       $params['bankId'] = 'ABC';
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
