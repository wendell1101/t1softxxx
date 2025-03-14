<?php
require_once dirname(__FILE__) . '/payment_api_rfupay.php';

/**
 * RFUPAY_PAYMENT_API, ID: 62
 *
 * The Gateway implementation of RFUPay
 *
 * @see Payment_api_rfupay
 * 
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rfupay_gateway extends Payment_api_rfupay {

	public function getPlatformCode() {
		return RFUPAY_PAYMENT_API;
	}

	protected function getAppType() {
		return '';
	}

	protected function getBankId($order) {
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return $extraInfo['bank'];
			}
		}
		return '';
	}

}