<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ulive.php';
/**
 * ULIVE
 *
 * * ULIVE_PAYMENT_API, ID: 5789
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.uuulive.net/integration/interface/create_task_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ulive extends Abstract_payment_api_ulive {

	public function getPlatformCode() {
		return ULIVE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ulive';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payment_type'] = $this->getSystemInfo('payment_type');
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}