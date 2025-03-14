<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zotapay.php';

/**
 *
 * * ZOTAPAY_QQPAY_PAYMENT_API, ID: 429
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://sandbox.zotapay.com/paynet/api/v2/sale-form/1
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zotapay_qqpay extends Abstract_payment_api_zotapay {

	public function getPlatformCode() {
		return ZOTAPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zotapay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}	

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrl($params);
	}
}
