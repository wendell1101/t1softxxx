<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pgtianci.php';
/**
 * PGTIANCI
 *
 * * PGTIANCI_ZALO_PAYMENT_API, ID: 5830
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
class Payment_api_pgtianci_zalo extends Abstract_payment_api_pgtianci {

    public function getPlatformCode() {
        return PGTIANCI_ZALOPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pgtianci_zalo';
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