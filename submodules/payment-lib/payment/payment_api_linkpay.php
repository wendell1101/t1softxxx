<?php
require_once dirname(__FILE__) . '/abstract_payment_api_linkpay.php';

/** 
 *
 * linkpay
 * 
 * 
 * * 'LINKPAY_PAYMENT_API', ID 5776
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://linkpay.surperpay.com/trade/unifiedOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_linkpay extends Abstract_payment_api_linkpay {

	public function getPlatformCode() {
		return LINKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'linkpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
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
