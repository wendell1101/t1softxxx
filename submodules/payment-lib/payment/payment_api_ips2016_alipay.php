<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ips2016.php';

/**
 * IPS 环迅支付 - 2016新版 （V0.3.13）
 * http://www.ips.com.cn/
 *
 * IPS2016_ALIPAY_PAYMENT_API - 185
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
 * * URL: https://thumbpay.e-years.com/psfp-webscan/services/scan?wsdl
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ips2016_alipay extends Abstract_payment_api_ips2016 {
	public function getPlatformCode() {
		return IPS2016_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ips2016_alipay';
	}

	protected function configParams(&$params, $order_id, $direct_pay_extra_info) {
		$params['GatewayType'] = self::GATEWAY_TYPE_ALIPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($xml) {
		return $this->processPaymentUrlQRCode($xml);
	}
}
