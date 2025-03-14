<?php
require_once dirname(__FILE__) . '/abstract_payment_api_islpay.php';
/**
 * ISLPAY 速龍支付
 *
 * * ISLPAY_JDPAY_PAYMENT_API, ID: 699
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
 * >    "scan_url": "https://api.islpay.hk/gateway/api/scanpay",
 * >    "h5_url": "https://api.islpay.hk/gateway/api/h5apipay"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_islpay_jdpay extends Abstract_payment_api_islpay {

	public function getPlatformCode() {
		return ISLPAY_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'islpay_jdpay';
    }

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			$islpay_way = $this->getSystemInfo("phone_way", "B2CH5");
		}else{
			$islpay_way =  $this->getSystemInfo("web_way", "SCAN");
		}

		switch ($islpay_way) {
			case "SCAN":
				$params['service_type'] = self::SERVICETYPE_JDPAY_SCAN;
				$params['interface_version'] = 'V3.1';
				break;
			case "H5":
				$params['service_type'] = self::SERVICETYPE_JDPAY_H5;
				$params['interface_version'] = 'V3.1';
				break;
			case "B2C":
				$params['interface_version'] = 'V3.0';
				$params['input_charset']     = 'UTF-8';
				$params['service_type']      = self::SERVICETYPE_DIRECTPAY;
				$params['pay_type']          = self::PAYTYPE_B2C_JDPAY;
				$params['redo_flag']         = 1; #当值为1时不允许商户订单号重复提交；当值为 0或空时允许商户订单号重复提交
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
		if($this->CI->utils->is_mobile()) {
			$islpay_way = $this->getSystemInfo("phone_way", "B2CH5");
		}else{
			$islpay_way =  $this->getSystemInfo("web_way", "SCAN");
		}

		switch ($islpay_way) {
			case "B2C":
			case "B2CH5":
				return $this->processPaymentUrlFormPost($params);
				break;
			case "SCAN":
			case "H5":
				return $this->processPaymentUrlFormQRCode($params);
				break;
		}
	}
}
