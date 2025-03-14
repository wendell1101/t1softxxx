<?php
require_once dirname(__FILE__) . '/abstract_payment_api_shuke.php';

/** 
 *
 * shuke  数科
 * 
 * 
 * * 'SHUKE_ALIPAY_H5_PAYMENT_API', ID 5011
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.f99000.com:7960/Submit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_shuke_alipay_h5 extends Abstract_payment_api_shuke {

	public function getPlatformCode() {
		return SHUKE_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'shuke_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = self::SCANTYPE_ALIPAY;
        $params['return_type'] = self::RETURN_TYPE_H5;     
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
