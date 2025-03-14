<?php
require_once dirname(__FILE__) . '/abstract_payment_api_guangxin.php';
/**
 * GUANGXIN 广信支付
 *
 * * GUANGXIN_QUICKPAY_PAYMENT_API, ID: 5062
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://api.6899q.cn/open/v1/quickPay/quick
 * * TOKEN URL: http://api.6899q.cn/open/v1/getAccessToken/merchant
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_guangxin_quickpay extends Abstract_payment_api_guangxin {

    public function getPlatformCode() {
        return GUANGXIN_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'guangxin_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['param']['cardName']     = '123';
        $params['param']['cardNo']       = '123';
        $params['param']['bank']         = '123';
        $params['param']['idType']       = '1';
        $params['param']['cardPhone']    = '123';
        $params['param']['cardIdNumber'] = '123';
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
