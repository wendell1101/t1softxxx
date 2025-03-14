<?php
require_once dirname(__FILE__) . '/abstract_payment_api_quickpay.php';
/**
 * QUICKPAY 快付
 *
 * * QUICKPAY_ALIPAY_PAYMENT_API, ID: 5797
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.quickpay123.com/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_quickpay_alipay extends Abstract_payment_api_quickpay {

    public function getPlatformCode() {
        return QUICKPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'quickpay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = $this->getSystemInfo('productId');

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