<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kakaloan_1.php';
/**
 * kakaloan_1 麒麟支付 网关 銀聯
 *
 * * KAKALOAN_1_QUICKPAY_PAYMENT_API, ID: 5084
 * *
 * Required Fields:
 * * URL: http://106.15.82.132:90/kakaloan/quick/cashierOrder
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kakaloan_1_quickpay extends Abstract_payment_api_kakaloan_1 {

	public function getPlatformCode() {
		return KAKALOAN_1_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kakaloan_1_quickpay';
	}

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
