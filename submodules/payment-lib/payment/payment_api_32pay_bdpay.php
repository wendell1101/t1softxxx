<?php
require_once dirname(__FILE__) . '/abstract_payment_api_32pay.php';
/**
 * 32PAY 32支付-百度
 *
 * * _32PAY_BDPAY_PAYMENT_API, ID: 515
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.32pay.com/Pay/KDBank.aspx
 * * Account: ## merchant ID ##
 * * Key: ## secret key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_32pay_bdpay extends Abstract_payment_api_32pay {

	public function getPlatformCode() {
		return _32PAY_BDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '32pay_bdpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['P_ChannelId'] = self::P_CHANNEL_BDPAY;
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
