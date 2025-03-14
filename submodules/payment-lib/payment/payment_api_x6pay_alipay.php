<?php
require_once dirname(__FILE__) . '/abstract_payment_api_x6pay.php';


/**
 * X6PAY (迅汇宝)
 * http://www.x6pay.com/
 *
 * X6PAY_ALIPAY_PAYMENT_API, ID: 139
 *
 * Required Fields:
 *
 * * URL
 * * Account (Merchant ID)
 * * Key (MD5 signing key)
 *
 * Field Values:
 *
 * * URL: http://pay.x6pay.com:8082/posp-api/passivePay
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_x6pay_alipay extends Abstract_payment_api_x6pay {

	public function getPlatformCode() {
		return X6PAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'x6pay_alipay';
	}

	public function getPayType() {
		return parent::PAYTYPE_ALIPAY;
	}

}
