<?php
require_once dirname(__FILE__) . '/abstract_payment_api_999pay.php';

/**
 * 汇天付 - 快捷支付
 * 
 *
 * _999PAY_QUICKPAY_PAYMENT_API, ID: 454
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
class Payment_api_999pay_quickpay extends Abstract_payment_api_999pay {

	public function getPlatformCode() {
		return _999PAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '999pay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['P_ChannelID'] = self::P_CHANNEL_QUICKPAY;
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
