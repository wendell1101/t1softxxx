<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tengyun.php';

/**
 *
 * * TENGYUN_WEIXIN_PAYMENT_API', ID: 5321
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: http://47.92.71.58/Pay_PayApi_PayRequest.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tengyun_weixin extends Abstract_payment_api_tengyun {

    public function getPlatformCode() {
        return TENGYUN_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tengyun_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::PAYTYPE_WEIXIN;    
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormUrl($params);
    }
}
