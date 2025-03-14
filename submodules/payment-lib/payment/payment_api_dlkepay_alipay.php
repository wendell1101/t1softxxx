<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dlkepay.php';

/** 
 *
 * dlkepay 联科支付 支付寶
 * 
 * 
 * * DLKEPAY_ALIPAY_PAYMENT_API, ID: 827
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
class Payment_api_dlkepay_alipay extends Abstract_payment_api_dlkepay {

	public function getPlatformCode() {
		return DLKEPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dlkepay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {

            $params['type'] = self::SCANTYPE_ALIPAY_H5;
		}
		else {
			$params['type'] = self::SCANTYPE_ALIPAY;
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
