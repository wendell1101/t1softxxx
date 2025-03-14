<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xy2pay.php';
/**
 * XY2PAY 祥云支付
 *
 * * XY2PAY_ALIPAY_BANKCARD_PAYMENT_API, ID: 5852
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://apzaqwe.xy2pay.cc/unionOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xy2pay_alipay_bankcard extends abstract_payment_api_xy2pay {

    public function getPlatformCode() {
        return XY2PAY_ALIPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'xy2pay_alipay_bankcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['service'] = $this->getSystemInfo('service',self::SERVICE_TYPE_ALIPAY);

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