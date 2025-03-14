<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wtzf888.php';

/**
 *
 * * WTZF888_WEIXIN_PAYMENT_API', ID: 5228
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: http://pay.wtzf168.com/v1/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wtzf888_weixin extends Abstract_payment_api_wtzf888 {

    public function getPlatformCode() {
        return WTZF888_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wtzf888_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_bankcode'] = self::PAY_BANKCODE_WEIXIN;
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
