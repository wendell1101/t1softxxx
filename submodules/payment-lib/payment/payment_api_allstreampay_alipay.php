<?php
require_once dirname(__FILE__) . '/abstract_payment_api_allstreampay.php';

/**
 * 全汇通 - 支付宝 ALLSTREAMPAY
 * http://gateway.allstreampay.com
 *
 * ALLSTREAMPAY_ALIPAY_PAYMENT_API, ID: 251
 *
 * Required Fields:
 * * URL
 * * Extra Info:
 * * {
 * *    "terminal_id"
 * *    "merchant_id"
 * *    "allstreampay_pub_key"
 * *    "allstreampay_priv_key"
 * * }
 *
 *
 * Field Values:
 * * URL: https://www.allstreampay.com/gateway/orderPay
 * * Extra Info:
 * * {
 * *    "terminal_id": ## Terminal ID ##,
 * *    "merchant_id": ## Merchant ID ##,
 * *    "allstreampay_pub_key" : "## pem formatted public key (escaped) ##",
 * *    "allstreampay_priv_key" : "## pem formatted private key (escaped) ##"
 * * }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_allstreampay_alipay extends Abstract_payment_api_allstreampay {

	public function getPlatformCode() {
		return ALLSTREAMPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'allstreampay_alipay';
	}

	public function getName() {
		return 'ALLSTREAMPAY_ALIPAY';
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
		return $this->processPaymentUrlFormPost($params);
	}

}
