<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hft138.php';

/** 
 *
 * HFT138 浩付通
 * 
 * 
 * * 'HFT138_ALIPAY_PAYMENT_API', ID 5342
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
class Payment_api_hft138_alipay extends Abstract_payment_api_hft138 {

	public function getPlatformCode() {
		return HFT138_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hft138_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
            $params['pay_bankcode'] = self::BANKCODE_ALIPAY;
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
