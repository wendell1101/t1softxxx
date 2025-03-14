<?php
require_once dirname(__FILE__) . '/abstract_payment_api_chhpay.php';

/**
 * CHHPAY 畅汇
 * https://t24o.cn/ 
 *
 * CHHPAY_PAYMENT_API, ID: 585
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://changcon.chhpay.com/controller.action
 * * Extra Info:
 * > {
 * > 	"chhpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"chhpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_chhpay extends Abstract_payment_api_chhpay {

	public function getPlatformCode() {
		return CHHPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'chhpay';
	}

	

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['pa_FrpId'] = self::PAYTYPE_BANK;
		$params['pg_BankCode'] = $bank;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
