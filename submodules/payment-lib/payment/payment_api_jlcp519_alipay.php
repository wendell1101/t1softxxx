<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eboo.php';

/** 
 *
 * JLCP519 
 * 
 * 
 * * 'JLCP519_ALIPAY_PAYMENT_API', ID 5523
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://new.themeeting.cn/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jlcp519_alipay extends Abstract_payment_api_eboo {

	public function getPlatformCode() {
		return JLCP519_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jlcp519_alipay';
	}


	protected function getBankCode() {
        return $this->getSystemInfo("bankcode", self::BANKCODE_ALIPAY);

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
