<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tdpay.php';
/**
 * TDPAY 顺博支付
 *
 * * TDPAY_QUICKPAY_PAYMENT_API, ID: 903
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://120.78.192.162:8014/tdpay-web-mer-portal/tdpay/umpay/quickPay.do
 * * Extra Info:
 * > {
 * >    "tdpay_priv_key": "## Private Key ##",
 * >    "tdpay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tdpay_quickpay extends Abstract_payment_api_tdpay {

    public function getPlatformCode() {
        return TDPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tdpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['gate_id'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }

        $params['charset']   = '1'; #UTF-8
        $params['notifyUrl'] = $params['temp']['notify_url'];
        $params['order_id']  = $params['temp']['secure_id'];
        $params['amount']    = $params['temp']['amount'];
        $params['amt_type']  = 'CNY';
        $params['card_Type'] = 'DEBITCARD'; #CREDITCARD(信用卡) DEBITCARD(借记卡)
        $params['goods_id']  = 'Topup';
        unset($params['temp']);
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
