<?php
require_once dirname(__FILE__) . '/abstract_payment_api_guangxin.php';
/**
 * GUANGXIN 广信支付
 *
 * * GUANGXIN_UNIONPAY_PAYMENT_API, ID: 5061
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://api.6899q.cn/open/v1/order/unionpayScan
 * * TOKEN URL: http://api.6899q.cn/open/v1/getAccessToken/merchant
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_guangxin_unionpay extends Abstract_payment_api_guangxin {

    public function getPlatformCode() {
        return GUANGXIN_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'guangxin_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        if($this->CI->utils->is_mobile()) {
            return $this->processPaymentUrlFormPost($params);
        }else{
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
