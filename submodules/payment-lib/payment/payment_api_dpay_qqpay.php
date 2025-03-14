<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_QQPAY_PAYMENT_API, ID: 331
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.273787.cn/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_qqpay extends Abstract_payment_api_dpay {

	public function getPlatformCode() {
		return DPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['scantype'] = self::SCANTYPE_QQPAY;
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
