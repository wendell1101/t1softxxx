<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hanpays.php';
/**
 * HANPAYS
 *
 * * HANPAYS_BANKCARD_PAYMENT_API, ID: 5780
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.hanpays.co/data/api/hanshi/receivables
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hanpays_bankcard extends Abstract_payment_api_hanpays {

    public function getPlatformCode() {
        return HANPAYS_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hanpays_bankcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::PAYTYPE_BANKCARD;
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