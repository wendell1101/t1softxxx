<?php
require_once dirname(__FILE__) . '/payment_api_yspay_weixin.php';

/**
 * YSPAY 广州银商/贝付 - 微信
 *
 *
 * YSPAY_WEIXIN_2_PAYMENT_API, ID: 862
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_Info
 *
 * Field Values:
 * * URL: http://www.xshuyu.com/pay/api.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra_Info: {"yspay_channel" : "## zftd code ##","yspay_mobile_channel" : "## zftd code ##"}
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yspay_weixin_2 extends Payment_api_yspay_weixin {

    public function getPlatformCode() {
        return YSPAY_WEIXIN_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yspay_weixin_2';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['zftd'] = $this->getSystemInfo("yspay_mobile_channel") ? $this->getSystemInfo("yspay_mobile_channel") : self::PAYTYPE_WEIXIN_2_H5;
        }else{
            $params['zftd'] = $this->getSystemInfo("yspay_channel") ? $this->getSystemInfo("yspay_channel") : self::PAYTYPE_WEIXIN_2;;
        }
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
}
