<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tdpay.php';
/**
 * TDPAY 顺博支付
 *
 * * TDPAY_ALIPAY_H5_PAYMENT_API, ID: 902
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://120.78.192.162:8014/tdpay-web-mer-portal/tdpay/apppay/pay.do
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
class Payment_api_tdpay_alipay_h5 extends Abstract_payment_api_tdpay {

    public function getPlatformCode() {
        return TDPAY_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tdpay_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['charset']   = '1'; #UTF-8
        $params['notifyUrl'] = $params['temp']['notify_url'];
        $params['orderNo']   = $params['temp']['secure_id'];
        $params['amount']    = $params['temp']['amount'];
        $params['currency']  = 'CNY';
        $params['appType']   = self::APPTYPE_ALIPAY;
        $params['orderTime'] = date("YmdHis", time());
        $params['orderName'] = 'Topup';
        unset($params['temp']);
    }

    public function getPlayerInputInfo() {
        /*
            1.
            Valid [float_amount_limit] pattern

            pattern: "float_amount_limit": "(A|B|C|D|E|F|...)"

            A: limit amount 1
            B: limit amount 2
            C: limit amount 3

            example: "float_amount_limit": "(1|21|51)"

            2.
            show [float_amount_limit_msg]  when amount is incorrect
        */
        $type = $this->getSystemInfo('float_amount_limit')? 'float_amount_limit' : 'float_amount' ;
        $float_amount_limit_msg = $this->getSystemInfo('float_amount_limit_msg')?$this->getSystemInfo('float_amount_limit_msg'):'請輸入上方金額';
        if($type == 'float_amount_limit'){
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'float_amount_limit_msg' => $float_amount_limit_msg),
            );
        }else{
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09'),
            );
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
