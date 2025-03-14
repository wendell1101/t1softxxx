<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_QUICKPAY_H5_PAYMENT_API, ID: 5113
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.273787.cn/api/cashierpay
 * * URL: http://api.273787.cn/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_quickpay_h5 extends Abstract_payment_api_dpay {

	public function getPlatformCode() {
		return DPAY_QUICKPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dpay_quickpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['scantype'] = self::SCANTYPE_QUICKPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormURL($params);
	}
}
