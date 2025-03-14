<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tongtai.php';
/**
 * TONGTAI 通泰
 *
 * * TONGTAI_ALIPAY_H5_PAYMENT_API, ID: 5175
 *
 * Required Fields:
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://69.172.75.141:7802/api.php/alipay/wap_pay (出碼)
 *        http://69.172.75.141:7802/api.php/dlipay/wap_pay (可以直連)
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tongtai_alipay_h5 extends Abstract_payment_api_tongtai {

    public function getPlatformCode() {
        return TONGTAI_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tongtai_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {
        if($this->getSystemInfo("use_app", true)){
            return $this->processPaymentUrlFormRedirect($params);
        }
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
