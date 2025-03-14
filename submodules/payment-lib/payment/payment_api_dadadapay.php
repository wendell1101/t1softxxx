<?php
require_once dirname(__FILE__) . '/abstract_payment_api_onegopay.php';

/**
 * DADADAPAY
 *
 * * DADADAPAY_PAYMENT_API, ID: 5959
 *
 * Required Fields:
 * same as ONEGOPAY API
 *
 * Field Values:
 * * URL: https://dadadapay.com/api/transaction
 * * Key: ## Access Token ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dadadapay extends Abstract_payment_api_onegopay {
    public function getPlatformCode() {
        return DADADAPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dadadapay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        unset($params['return_url']);
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