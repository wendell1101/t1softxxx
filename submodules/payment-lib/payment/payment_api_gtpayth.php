<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gtpayth.php';

/**
 * GTPAYTH
 * *
 * * GTPAYTH_PAYMENT_API, ID: 6162
 *
 * Required Fields:
 * * URL:
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gtpayth extends Abstract_payment_api_gtpayth {

    public function getPlatformCode() {
        return GTPAYTH_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gtpayth';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = self::PAYTYPE_BANK_TO_BANK;
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
