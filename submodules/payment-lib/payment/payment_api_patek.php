<?php
require_once dirname(__FILE__) . '/abstract_payment_api_patek.php';

/**
 *
 * * PATEK_PAYMENT_API, ID: 5715
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.ppthb888.com/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_patek extends Abstract_payment_api_patek {

	public function getPlatformCode() {
		return PATEK_PAYMENT_API;
	}

	public function getPrefix() {
		return 'patek';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

		$params['paytype'] = $this->getSystemInfo("paytype") ? $this->getSystemInfo("paytype") : self::PAYTYPE_QRCODE;
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
