<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sevpay.php';

/**
 * SEVPAY
 * * http://merchant.777office.com/
 *
 * * SEVPAY_ALIPAY_PAYMENT_API, ID: 912
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.paynow777.com/merchanttransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sevpay_alipay extends Abstract_payment_api_sevpay {

    public function getPlatformCode() {
        return SEVPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sevpay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['Bank'] = self::BANK_ALIPAY;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
