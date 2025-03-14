<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huibo.php';

/**
 * HuiBo 汇博支付
 *
 * HUIBO_PAYMENT_API, ID: 117
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://47.90.92.130:9899/HBConn/online
 * * Account: ## Merchant account ##
 * * Extra Info
 * > {
 * >     "huibo_priv_key": "## path to merchant's private key ##",
 * >     "huibo_pub_key" : "## path to merchant's public key ##",
 * >     "huibo_api_pub_key" : "## path to API's public key ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huibo extends Abstract_payment_api_huibo {
	public function getPlatformCode() {
		return HUIBO_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huibo';
	}

	protected function getChannelCode() {
		return 'UNIPAY';
	}

	protected function getOrderCode() {
		return 'hb_onLinePay';
	}

	# Hide bank list dropdown as there is no bank list specified in documentation
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function handlePaymentUrlForm($params, $context) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}
}