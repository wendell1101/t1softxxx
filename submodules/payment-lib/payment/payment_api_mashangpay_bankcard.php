<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mashangpay.php';
/**
 * MASHANGPAY 码上支付 
 *
 * * MASHANGPAY_BANKCARD_PAYMENT_API, ID: 5892
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payment.dev.mspays.xyz/haoli711/orders/v3/scan
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mashangpay_bankcard extends Abstract_payment_api_mashangpay {

    public function getPlatformCode() {
        return MASHANGPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'mashangpay_bankcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paymentTypeCode'] = $this->getSystemInfo('paymentTypeCode',self::PAYMENTTYPE_BANKTOBANK);
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