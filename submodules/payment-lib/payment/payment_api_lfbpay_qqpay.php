<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lfbpay.php';

/**
 * LFBPAY 乐付宝 - QQ
 * 
 *
 * LFBPAY_QQPAY_PAYMENT_API, ID: 391
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://trade.test.com/cooperate/gateway.cgi
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lfbpay_qqpay extends Abstract_payment_api_lfbpay {

	public function getPlatformCode() {
		return LFBPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lfbpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['service'] = self::SERVICE_SCANPAY;		
		$params['typeId'] = self::PAYTYPE_QQPAY;	
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
