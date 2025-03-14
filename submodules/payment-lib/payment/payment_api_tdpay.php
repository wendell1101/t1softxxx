<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tdpay.php';
/**
 * TDPAY 顺博支付
 *
 * * TDPAY_PAYMENT_API, ID: 901
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://39.106.2.9:8081/tdpay
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
class Payment_api_tdpay extends Abstract_payment_api_tdpay {

    public function getPlatformCode() {
        return TDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tdpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['inputCharset'] = '1'; #UTF-8
        $params['submitType']   = '00';
        $params['bgUrl']        = $params['temp']['notify_url'];
        $params['qryTimestamp'] = date("YmdHis", time());
        $params['payType']      = '0';
        $params['jumpType']     = '00';
        $params['paymentType']  = '2';
        $params['loginType']    = '1';
        $params['orderNo']      = $params['temp']['secure_id'];
        $params['currency']     = 'CNY';
        $params['orderAmount']  = $params['temp']['amount'];
        $params['orderTime']    = date("YmdHis", time());
        $params['productDesc']  = 'Topup';
        $params['isGuarant']    = '0';
        $params['merPayType']   = self::MERPAYTYPE_WEIXIN;
        unset($params['temp']);
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
