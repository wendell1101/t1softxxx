<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sufa.php';
/**
 * SUFA
 *
 * * SUFA_ALIPAY_PAYMENT_API, ID: 5731
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.xinwuw.com:19326/qrdeposit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sufa_alipay extends Abstract_payment_api_sufa {

    public function getPlatformCode() {
        return SUFA_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sufa_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['types'] = self::PAYTYPE_ALIPAY;
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