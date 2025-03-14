<?php
require_once dirname(__FILE__) . '/abstract_payment_api_passapi.php';

/**
 * PASSAPI
 *
 * * PASSAPI_ALIPAY_H5_PAYMENT_API, ID: 5185
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://119.29.115.76/preCreate
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_passapi_alipay_h5 extends Abstract_payment_api_passapi {

    public function getPlatformCode() {
        return PASSAPI_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'passapi_alipay_h5';
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
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