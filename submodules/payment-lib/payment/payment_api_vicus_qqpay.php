<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vicus.php';

/**
 *
 * * VICUS_QQPAY_PAYMENT_API, ID: 703
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.vicussolutions.net/Payapi_Index_Pay.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vicus_qqpay extends Abstract_payment_api_vicus {

	public function getPlatformCode() {
		return VICUS_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vicus_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pd_FrpId'] = self::PAYTYPE_QQPAY;
		$params['Vicus_Paytype'] = self::PAYTYPE_QQPAY;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
