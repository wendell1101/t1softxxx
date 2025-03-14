<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rpn.php';

/**
 * RPN
 *
 * * RPN_QUICKPAY_PAYMENT_API, ID: 812
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://deposit.paylomo.net/pay.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_quickpay extends Abstract_payment_api_rpn {

	public function getPlatformCode() {
		return RPN_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
