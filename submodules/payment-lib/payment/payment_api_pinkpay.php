<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pinkpay.php';
/**
 * pinkpay
 * *
 * * PINKPAY_PAYMENT_API, ID: 6114
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://a.pinkpay.im/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pinkpay extends Abstract_payment_api_pinkpay {

    public function getPlatformCode() {
        return PINKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pinkpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::BUSICODE_BANK;
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
