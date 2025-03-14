<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kongfubau.php';
/**
 * KONGFUBAU 共富宝
 *
 * * KONGFUBAU_ALIPAY_PAYMENT_API, ID: 5631
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://kongfubau.com/api/transaction
 * * Key: ## Access Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kongfubau_alipay extends Abstract_payment_api_kongfubau {

    public function getPlatformCode() {
        return KONGFUBAU_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kongfubau_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
       
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
