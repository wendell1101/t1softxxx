<?php
require_once dirname(__FILE__) . '/abstract_payment_api_islpay.php';
/**
 * ISLPAY 速龍支付
 *
 * * ISLPAY_JDPAY_H5_PAYMENT_API, ID: 733
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
 * >    "h5_url": "https://api.islpay.hk/gateway/api/h5apipay"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_islpay_jdpay_h5 extends Abstract_payment_api_islpay {

	public function getPlatformCode() {
		return ISLPAY_JDPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'islpay_jdpay_h5';
    }

	protected function configParams(&$params, $direct_pay_extra_info) {
		$islpay_way = $this->getSystemInfo("phone_way", "B2CH5");
		switch ($islpay_way) {
			case "H5":
				$params['service_type'] = self::SERVICETYPE_JDPAY_H5;
				$params['interface_version'] = 'V3.1';
				break;
			case "B2CH5":
				$params['service_type'] = self::SERVICETYPE_JDPAY_B2CH5;
				$params['interface_version'] = 'V3.0';
				$params['input_charset'] = 'UTF-8';
				break;
		}
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		$islpay_way = $this->getSystemInfo("phone_way", "B2CH5");
		switch ($islpay_way) {
			case "H5":
				return $this->processPaymentUrlFormQRCode($params);
				break;
			case "B2CH5":
				return $this->processPaymentUrlFormPost($params);
				break;
		}
	}
}
