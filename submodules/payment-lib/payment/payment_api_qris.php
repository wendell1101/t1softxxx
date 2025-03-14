<?php
require_once dirname(__FILE__) . '/abstract_payment_api_qris.php';

/**
 *
 * qris
 * *
 * *
 * * QRIS_PAYMENT_API, ID: 6367

 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://qris.otomatis.vip/api/generate
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qris extends Abstract_payment_api_qris {

	public function getPlatformCode() {
		return QRIS_PAYMENT_API;
	}

	public function getPrefix() {
		return 'qris';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		// $params['pmId'] = self::PAYTYPE_CPF;
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
