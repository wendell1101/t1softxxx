<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rpn.php';

/**
 * RPN
 *
 * * RPN_ALIPAY_PAYMENT_API, ID: 814
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
class Payment_api_rpn_alipay extends Abstract_payment_api_rpn {

	public function getPlatformCode() {
		return RPN_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->getSystemInfo('use_version') == '1.1'){
			$params['version'] = '1.1'; # fixed value
			unset($params['return_url']);
		}
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
