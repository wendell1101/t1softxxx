<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinmapay.php';

/**
 * XINMAPAY 新码支付 - 支付宝
 * 
 *
 * XINMAPAY_ALIPAY_PAYMENT_API, ID: 377
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.xinmapay.com:7301/jhpayment
 * * Account : ## branch_id ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xinmapay_alipay extends Abstract_payment_api_xinmapay {

	public function getPlatformCode() {
		return XINMAPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xinmapay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['pay_type'] = self::PAYTYPE_ALIPAY;
		$params['messageid'] = '200001';
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlQRCode($params);
	}

}
