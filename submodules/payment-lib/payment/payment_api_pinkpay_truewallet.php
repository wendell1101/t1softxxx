<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pinkpay.php';
/**
 * pinkpay
 * *
 * * PINKPAY_TRUEWALLET_PAYMENT_API, ID: 6116
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
class Payment_api_pinkpay_truewallet extends Abstract_payment_api_pinkpay {

    public function getPlatformCode() {
        return PINKPAY_TRUEWALLET_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pinkpay_truewallet';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::BUSICODE_TRUEPAY;
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
