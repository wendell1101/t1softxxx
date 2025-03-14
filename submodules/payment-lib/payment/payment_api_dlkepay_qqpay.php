<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dlkepay.php';

/** 
 *
 * dlkepay 联科支付 QQ
 * 
 * 
 * * DLKEPAY_QQPAY_PAYMENT_API, ID: 829
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
class Payment_api_dlkepay_qqpay extends Abstract_payment_api_dlkepay {

	public function getPlatformCode() {
		return DLKEPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dlkepay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {

            $params['type'] = self::SCANTYPE_QQPAY_H5;
		}
		else {
			$params['type'] = self::SCANTYPE_QQPAY;
		}
	}


	protected function processPaymentUrlForm($params) {

			return $this->processPaymentUrlFormPost($params);
		
	}

	public function getPlayerInputInfo() {

       return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );

    }

}
