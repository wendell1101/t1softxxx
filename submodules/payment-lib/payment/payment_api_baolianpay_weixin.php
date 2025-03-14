<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bingopay.php';
/**
 * BAOLIANPAY  宝联付 deposit 微信 繼承bingopay
 * 
 *
 * BAOLIANPAY_WEIXIN_PAYMENT_API, ID: 5035
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://147.92.33.235:18888/open-gateway/trade/invoke
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_baolianpay_weixin extends Abstract_payment_api_bingopay {

	public function getPlatformCode() {
		return BAOLIANPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'baolianpay_weixin';
	}

	protected function configParams(&$params,&$data, $direct_pay_extra_info) {
				
		$params['bus_no'] = self::DEFAULTNANK_WEIXIN;
		$data['productId']=self::DEFAULTNANK_QRCODE;
	
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

		return $this->processPaymentUrlFormQRCode($params);
		
	}

}
