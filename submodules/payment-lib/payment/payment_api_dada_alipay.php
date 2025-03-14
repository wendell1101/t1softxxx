<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dada.php';
/**
 * DADA 达达
 *
 * * 'DADA_ALIPAY_PAYMENT_API', ID 5350
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## merchant ID ##
 * * Key: ## secret key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dada_alipay extends Abstract_payment_api_dada {

	public function getPlatformCode() {
		return DADA_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dada_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['method'] = self::PAYTYPE_ALIPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
