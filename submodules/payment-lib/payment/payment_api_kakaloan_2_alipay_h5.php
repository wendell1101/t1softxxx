<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kakaloan_2.php';
/**
 * kakaloan_2 麒麟支付
 *
 * * KAKALOAN_2_ALIPAY_H5_PAYMENT_API, ID: 5095
 * *
 * Required Fields:
 * * URL: http://106.15.82.132:89/Home/Open/AliH5Pay
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kakaloan_2_alipay_h5 extends Abstract_payment_api_kakaloan_2 {

	public function getPlatformCode() {
		return KAKALOAN_2_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kakaloan_2_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info, $amount) {
		$params['totalAmount'] = $this->convertAmountToCurrency($amount); //分
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
