<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kanpay.php';
/**
 * KANPAY 
 * 
 *
 * KANPAY_ALIPAY_PAYMENT_API, ID: 792
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.kanpay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kanpay_alipay extends Abstract_payment_api_kanpay {

	public function getPlatformCode() {
		return KANPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kanpay_alipay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
		
		if($this->CI->utils->is_mobile()) {
			$params['pay_bankcode'] = $this->getSystemInfo("phone_code");
		}else{
			$params['pay_bankcode'] = $this->getSystemInfo("web_code");
		}
		
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
        $float_amount_limit_msg = $this->getSystemInfo('float_amount_limit_msg')?$this->getSystemInfo('float_amount_limit_msg'):'请输入金额100,200,300,500,800,1000,1500,2000,2500,3000';
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
