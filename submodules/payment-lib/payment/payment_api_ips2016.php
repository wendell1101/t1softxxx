<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ips2016.php';

/**
 * IPS 环迅支付 - 2016新版 （V0.3.13）
 * http://www.ips.com.cn/
 *
 * IPS2016_PAYMENT_API - 184
 *
 * Required Fields:
 *
 * * URL
 * * Key - Merchant ID
 * * Account - Merchant Account Number
 * * Secret - MD5 key
 *
 * Field Values:
 *
 * * URL: https://newpay.ips.com.cn/psfp-entry/gateway/payment.do
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ips2016 extends Abstract_payment_api_ips2016 {

	public function getPlatformCode() {
		return IPS2016_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ips2016';
	}

	protected function configParams(&$params, $orderId, $direct_pay_extra_info) {
		$params['OrderEncodeType'] = self::ORDER_ENCODE_TYPE_MD5;

		$params['IsCredit'] = '0'; # Set to 1 will get an error: 该产品不支持直连！
		$params['ProductType'] = '1';
		$params['Merchanturl'] = $this->getReturnUrl($orderId);
		$params['FailUrl'] = $this->getReturnUrlFail($orderId);
		$params['RetType'] = self::RET_TYPE_SERVER;

		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['BankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function processPaymentUrlForm($xml) {
		return $this->processPaymentUrlPost($xml);
	}
}
