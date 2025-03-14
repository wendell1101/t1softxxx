<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wanweipay.php';

/**
 *
 * * WANWEIPAY_PAYMENT_API, ID: 5662
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.247pay.site/api/v1/payin/pay_info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wanweipay extends Abstract_payment_api_wanweipay {

	public function getPlatformCode() {
		return WANWEIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'wanweipay';
	}

	public function getBankType($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channelId'] = self::CHANNELID_BANK;
		$params['depositBankCode'] = $this->getBankType($direct_pay_extra_info);
		unset($params['depositName']);
	}

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}
