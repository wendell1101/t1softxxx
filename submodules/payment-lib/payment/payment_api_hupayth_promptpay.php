<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hupayth.php';

/**
 * HUPAYTH
 *
 * * HUPAYTH_PROMPTPAY_PAYMENT_API, ID: 5890
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:  http://www.huvnd.com/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hupayth_promptpay extends Abstract_payment_api_hupayth {

    public function getPlatformCode() {
        return HUPAYTH_PROMPTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hupayth_promptpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::PAYMETHOD_PROMPTPAY;
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