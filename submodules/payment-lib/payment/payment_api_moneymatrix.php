<?php
require_once dirname(__FILE__) . '/abstract_payment_api_moneymatrix.php';
/**
 * MONEYMATRIX
 *
 * * MONEYMATRIX_PAYMENT_API, ID: 5077
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.moneymatrix.com/api/v1/Hosted/InitDeposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_moneymatrix extends Abstract_payment_api_moneymatrix {

    public function getPlatformCode() {
        return MONEYMATRIX_PAYMENT_API;
    }

    public function getPrefix() {
        return 'moneymatrix';
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
