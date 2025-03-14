<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vicus.php';

/**
 *
 * * VICUS_ALIPAY_PAYMENT_API, ID: 256
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
class Payment_api_vicus_alipay extends Abstract_payment_api_vicus {

	public function getPlatformCode() {
		return VICUS_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vicus_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pd_FrpId'] = self::PAYTYPE_ALIPAY;
		$params['Vicus_Paytype'] = self::PAYTYPE_ALIPAY;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
