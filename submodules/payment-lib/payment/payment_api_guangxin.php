<?php
require_once dirname(__FILE__) . '/abstract_payment_api_guangxin.php';
/**
 * GUANGXIN 广信支付
 *
 * * GUANGXIN_PAYMENT_API, ID: 5060
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://api.6899q.cn/open/v1/order/bankPay
 * * TOKEN URL: http://api.6899q.cn/open/v1/getAccessToken/merchant
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_guangxin extends Abstract_payment_api_guangxin {

    public function getPlatformCode() {
        return GUANGXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'guangxin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['param']['bankName'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
