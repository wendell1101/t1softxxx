<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zhifu.php';

/**
 * ZHIFU 知付
 *
 * * ZHIFU_QUICKPAY_PAYMENT_API, ID:
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.aido88.cn/api_deposit.shtml
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zhifu_quickpay extends Abstract_payment_api_zhifu {

    public function getPlatformCode() {
        return ZHIFU_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'zhifu_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['Mode'] = self::MODE_QUICKPAY;
        $params['BankCode'] = self::BANKCODE_UNIONPAY;
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
