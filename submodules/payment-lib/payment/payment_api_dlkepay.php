<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dlkepay.php';

/** 
 *
 * dlkepay 联科支付 網銀
 * 
 * 
 * * DLKEPAY_PAYMENT_API, ID: 826
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
class Payment_api_dlkepay extends Abstract_payment_api_dlkepay {

	public function getPlatformCode() {
		return DLKEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dlkepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		 $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
         
       	$params['type'] = $bank;




  //       if($this->CI->utils->is_mobile()) {

  //           $params['type'] = ($this->getSystemInfo('usepcali')) ?self::SCANTYPE_ALIPAY : self::SCANTYPE_ONLINEBANK_H5;
		// }
		// else {
		// 	$params['type'] = self::SCANTYPE_ONLINEBANK_H5;
		// }
	}


	protected function processPaymentUrlForm($params) {

			return $this->processPaymentUrlFormPost($params);
		
	}

	// public function getPlayerInputInfo() {

 //       return array(
 //            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
 //        );

 //    }

}
