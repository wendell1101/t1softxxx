<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eferpay.php';
/**
 * EFERPAY
 *
 * * EFERPAY_QUICKPAY_PAYMENT_API, ID: 5212
 *
 * Required Fields:
 * * Account
 * * Key
 * * Secret
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.eferpay.com/oss/wallet/crepay_order_v1
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_eferpay_quickpay extends Abstract_payment_api_eferpay {

    public function getPlatformCode() {
        return EFERPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'eferpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['order_money_min'] = $params['order_money'];
        $params['order_money_max'] = $params['order_money'];
        unset($params['order_money']);
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
