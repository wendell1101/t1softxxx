<?php
require_once dirname(__FILE__) . '/abstract_payment_api_elmo.php';

/**
 * ELMO
 *
 * * ELMO_PAYMENT_API, ID: 5894
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.elmo1.com:9578/interface/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_elmo extends Abstract_payment_api_elmo {

    public function getPlatformCode() {
        return ELMO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'elmo';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::CODE_TYPE_ONLINEBANK;

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
