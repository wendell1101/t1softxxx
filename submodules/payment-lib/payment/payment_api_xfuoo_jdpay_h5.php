<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xfuoo.php';

/**
 *
 * * XFUOO_JDPAY_H5_PAYMENT_API, ID: 399
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ebank.xfuoo.com
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xfuoo_jdpay_h5 extends Abstract_payment_api_xfuoo {

	public function getPlatformCode() {
		return XFUOO_JDPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xfuoo_jdpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['defaultbank'] = self::DEFAULTBANK_JDPAY;
		$params['isApp']       = 'H5';
		$params['userIp']      = $this->getClientIp();
		$params['appName']     = 'Deposit';
		$params['appMsg']      = 'Deposit';
		$params['appType']     = 'wap';
		$params['backUrl']     = $params['returnUrl'];
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
