<?php
require_once dirname(__FILE__) . '/abstract_payment_api_islpay.php';
/**
 * ISLPAY 速龍支付
 *
 * * ISLPAY_PAYMENT_API, ID: 573
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Extra Info:
 * > {
 * >    "islpay_priv_key": "## Private Key ##",
 * >    "islpay_pub_key": ## Public Key ##"",
 * >    "b2c_url": "https://pay.islpay.hk/gateway?input_charset=UTF-8",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_islpay extends Abstract_payment_api_islpay {

	public function getPlatformCode() {
		return ISLPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'islpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		$params['interface_version'] = 'V3.0';
		$params['input_charset']     = 'UTF-8';
		$params['service_type']      = self::SERVICETYPE_DIRECTPAY;
		$params['pay_type']          = self::PAYTYPE_B2C_BANK;
		$params['redo_flag']         = 1; #当值为1时不允许商户订单号重复提交；当值为 0或空时允许商户订单号重复提交
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
