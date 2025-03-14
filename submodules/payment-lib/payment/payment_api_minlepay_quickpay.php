<?php
require_once dirname(__FILE__) . '/abstract_payment_api_minle.php';

/**
 *
 * 民乐
 *
 *
 * * MINLEPAY_QUICKPAY_PAYMENT_API, ID: 548
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * LIVE-URL: https://upay.szyinfubao.com/quickPay/pay
 * * TEST-URL: http://routepay.snsshop.net/quickPay/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_minlepay_quickpay extends Abstract_payment_api_minle {

    public function getPlatformCode() {
        return MINLEPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'minlepay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::TYPE_QUICKPAY;
        $params['card_type'] = self::CARD_TYPE;
    }


    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);

    }

    //Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
