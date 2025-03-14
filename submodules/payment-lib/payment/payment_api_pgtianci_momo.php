<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pgtianci.php';
/**
 * PGTIANCI
 *
 * * PGTIANCI_MOMO_PAYMENT_API, ID: 5829
 *
 * Required Fields:
 * * URL
 * * Key
 * * uid (merchant ID)
 *
 * Field Values:
 * * URL        : https://tianciv990901.com/api/transaction
 * * Key        : ## Live key ##
 * * uid        : ## merchant ID ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pgtianci_momo extends Abstract_payment_api_pgtianci {

    public function getPlatformCode() {
        return PGTIANCI_MOMO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pgtianci_momo';
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