<?php
require_once dirname(__FILE__) . '/abstract_payment_api_minle.php';

/**
 *
 * 民乐
 *
 *
 * * MINLEPAY_UNIONPAY_2_H5_PAYMENT_API, ID: 5527
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: minlepay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_minlepay_unionpay_2_h5 extends Abstract_payment_api_minle {

    public function getPlatformCode() {
        return MINLEPAY_UNIONPAY_2_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'minlepay_unionpay_2_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::TYPE_UNIONPAY_CL_H5;
        $params['card_type'] = self::CARD_TYPE;
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
