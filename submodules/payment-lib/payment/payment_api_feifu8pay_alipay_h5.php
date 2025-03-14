<?php
require_once dirname(__FILE__) . '/abstract_payment_api_feifu8pay.php';

/** 
 *
 * feifu8  菲付吧
 * 
 * 
 * * FEIFU8PAY_ALIPAY_H5_PAYMENT_API, ID: 5101
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.cutantan.net/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_feifu8pay_alipay_h5 extends Abstract_payment_api_feifu8pay {

	public function getPlatformCode() {
		return FEIFU8PAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'feifu8pay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_bankcode'] = self::SCANTYPE_ALIPAY_H5;
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
