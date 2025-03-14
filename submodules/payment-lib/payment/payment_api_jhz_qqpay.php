<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jhz.php';

/**
 * JHZ 金海哲 - QQ
 * http://wz.szjhzxxkj.com
 *
 * JHZ_QQPAY_PAYMENT_API - 228
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: http://zf.szjhzxxkj.com/ownPay/pay
 * * Extra Info:
 * > {
 * > 	"jhz_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"jhz_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jhz_qqpay extends Abstract_payment_api_jhz {

	public function getPlatformCode() {
		return JHZ_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jhz_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['payMethod'] = self::PAY_METHOD_QQPAY;
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
