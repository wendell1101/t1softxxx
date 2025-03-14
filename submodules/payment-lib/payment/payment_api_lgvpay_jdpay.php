<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lgvpay.php';

/**
 *
 * * LGVPAY_JDPAY_PAYMENT_API, ID: 475
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://lycs.uas-gw.info/dev/deposit/leying/forward
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lgvpay_jdpay extends Abstract_payment_api_lgvpay {

	public function getPlatformCode() {
		return LGVPAY_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lgvpay_jdpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['gateway'] = self::GATEWAY_JDPAY;
	}	

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlGetMethod($params);
	}
}
