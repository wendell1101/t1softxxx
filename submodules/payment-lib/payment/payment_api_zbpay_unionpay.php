<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zbpay.php';

/**
 * ZBPAY 众宝支付
 * https://merchant.zbpay365.com/
 *
 * ZBPAY_UNIONPAY_PAYMENT_API, ID: 642
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Secret Key
 *
 * Field Values:
 *
 * * URL: https://gateway.zbpay365.com/GateWay/Pay
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zbpay_unionpay extends Abstract_payment_api_zbpay {

    public function getPlatformCode() {
        return ZBPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'zbpay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::PAYTYPE_UNIONPAY;
    }

    # Hide bank selection drop-down
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
        if($this->CI->utils->is_mobile()) {
            return $this->processPaymentUrlFormPost($params);
        }else{
            return $this->processPaymentUrlFormPost($params);
        }
    }
}
