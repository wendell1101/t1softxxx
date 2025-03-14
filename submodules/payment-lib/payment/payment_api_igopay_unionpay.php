<?php
require_once dirname(__FILE__) . '/abstract_payment_api_igopay.php';

/**
 * IGOPAY
 *
 * * IGOPAY_UNIONPAY_PAYMENT_API, ID: 5530
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://cp66.site/zpay/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_igopay_unionpay extends Abstract_payment_api_igopay {

	public function getPlatformCode() {
		return IGOPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'igopay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payType'] = self::PAY_TYPE_UNIONPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

}
