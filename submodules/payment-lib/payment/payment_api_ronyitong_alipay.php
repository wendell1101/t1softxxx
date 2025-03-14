<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ronyitong.php';

/** 
 *
 * ronyitong  荣亿通 支付寶
 * 
 * 
 * * 'RONYITONG_ALIPAY_PAYMENT_API', ID 875
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.feifu8.com/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ronyitong_alipay extends Abstract_payment_api_ronyitong {

	public function getPlatformCode() {
		return RONYITONG_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ronyitong_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        
            $params['payType'] = self::SCANTYPE_ALIPAY;
	}

	protected function processPaymentUrlForm($params) {

			return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        
            return array(
                 array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            );
    }

}
