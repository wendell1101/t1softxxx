<?php
require_once dirname(__FILE__) . '/abstract_payment_api_999pay.php';

/**
 * 汇天付 - 京东
 * 
 *
 * _999PAY_JDPAY_PAYMENT_API, ID: 453
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.999pays.com/Pay/KDBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_999pay_jdpay extends Abstract_payment_api_999pay {

	public function getPlatformCode() {
		return _999PAY_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '999pay_jdpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['P_ChannelID'] = self::P_CHANNEL_JDPAY;
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
