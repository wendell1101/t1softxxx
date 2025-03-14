<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sanlian.php';
/**
 * SANLIAN
 *
 * * SANLIAN_PAYMENT_API, ID: 5934
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.request_key
 * * extra_info.callback_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.request_key      ## request key ##
 * * extra_info.callback_key     ## callback key ##
 *
 * @see         abstract_payment_api_sanlian.php
 * @category    Payment
 * @copyright   2022 tot
 */
class Payment_api_sanlian extends Abstract_payment_api_sanlian {

    public function getPlatformCode() {
        return SANLIAN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sanlian';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_id']   = self::PAY_REQ_PAY_ID_CARD2CARD;
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