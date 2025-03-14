<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bgpay.php';

/**
 * BGPAY 聚合支付
 * *
 * * BGPAY_WEIXIN_PAYMENT_API, ID: 5235
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.1115187.com/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bgpay_weixin extends Abstract_payment_api_bgpay {

    public function getPlatformCode() {
        return BGPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bgpay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        // if($this->CI->utils->is_mobile()){
        //     $params['fxpay'] = self::FXPAY_WEIXIN_H5;
        // }
        // else{
            $params['fxpay'] = self::FXPAY_WEIXIN;
        }
    // }

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
        return $this->processPaymentUrlFormPost($params);
    }
}
