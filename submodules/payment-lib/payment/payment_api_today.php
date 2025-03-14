<?php
require_once dirname(__FILE__) . '/abstract_payment_api_today.php';

/** 
 *
 * TODAY
 * 
 * * 'TODAY_PAYMENT_API', ID 6107
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_today extends Abstract_payment_api_today {

	public function getPlatformCode() {
		return TODAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'today';
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
