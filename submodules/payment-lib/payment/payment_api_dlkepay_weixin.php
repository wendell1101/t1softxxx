<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dlkepay.php';

/** 
 *
 * dlkepay 联科支付 微信
 * 
 * 
 * * DLKEPAY_WEIXIN_PAYMENT_API, ID: 828
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.dlkepay.com/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dlkepay_weixin extends Abstract_payment_api_dlkepay {

	public function getPlatformCode() {
		return DLKEPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dlkepay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {

            $params['type'] = self::SCANTYPE_WEIXIN_H5;
		}
		else {
			$params['type'] = self::SCANTYPE_WEIXIN;
		}
	}


	protected function processPaymentUrlForm($params) {

			return $this->processPaymentUrlFormPost($params);
		
	}

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

}
