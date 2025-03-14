<?php
require_once dirname(__FILE__) . '/abstract_payment_api_golbalpay.php';

/**
 *
 * * GOLBALPAY_PAYMENT_API, ID: 6013
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ckus.kighj.com/ty/orderPay
 * * Account : ## branch_id ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_golbalpay extends Abstract_payment_api_golbalpay {

	public function getPlatformCode() {
		return GOLBALPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'golbalpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		// $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		// if (!empty($direct_pay_extra_info)) {
		// 	$extraInfo = json_decode($direct_pay_extra_info, true);
		// 	if (!empty($extraInfo)) {
		// 		$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
		// 	}
		// }
		$params['busi_code'] = self::BUSICODE_BANK;
		// $params['bankCode'] = $bank;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormRedirect($params);
	}
}
