<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianhe.php';

/**
 *
 * TIANHE
 *
 * * TIANHE_BANKCARD_PAYMENT_API, ID: 5794
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.22222269.com/index/unifiedorder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tianhe_bankcard extends Abstract_payment_api_tianhe {

	public function getPlatformCode() {
		return TIANHE_BANKCARD_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tianhe_bankcard';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = $this->getSystemInfo('pay_type');
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
