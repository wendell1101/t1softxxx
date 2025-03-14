<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eboo.php';

/** 
 *
 * VB2B 巅峰聚合
 * 
 * 
 * * 'VB2B_ALIPAY_H5_PAYMENT_API', ID 5349
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vb2b_alipay_h5 extends Abstract_payment_api_eboo {

	public function getPlatformCode() {
		return VB2B_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vb2b_alipay_h5';
	}


	protected function getBankCode() {
        return $this->getSystemInfo("bankcode", self::BANKCODE_ALIPAY_H5);

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
