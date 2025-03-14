<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yinbangpay.php';

/**
 * 银邦支付 - 支付宝 YINBANGPAY
 * https://sup.yinbangpay.com/
 *
 * YINBANGPAY_ALIPAY_PAYMENT_API, ID: 218
 *
 * Required Fields:
 * * URL
 * * Extra Info:
 * * {
 * *    "terminal_id"
 * *    "merchant_id"
 * *    "yinbangpay_pub_key"
 * *    "yinbangpay_priv_key"
 * * }
 *
 *
 * Field Values:
 * * URL: https://www.yinbangpay.com/gateway/orderPaySweepCode
 * * Extra Info:
 * * {
 * *    "terminal_id": ## Terminal ID ##,
 * *    "merchant_id": ## Merchant ID ##,
 * *    "yinbangpay_pub_key" : "## pem formatted public key (escaped) ##",
 * *    "yinbangpay_priv_key" : "## pem formatted private key (escaped) ##"
 * * }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yinbangpay_alipay extends Abstract_payment_api_yinbangpay {

	public function getPlatformCode() {
		return YINBANGPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yinbangpay_alipay';
	}

	public function getName() {
		return 'YINBANGPAY_ALIPAY';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payType'] = self::PAY_TYPE_CODE_ALIPAY;
		unset($params['appSence']);
		unset($params['syncURL']);
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
