<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kakaloan_2.php';
/**
 * kakaloan_2 麒麟支付 网关 銀聯
 *
 * * KAKALOAN_2_UNIONPAY_PAYMENT_API, ID: 5096
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
class Payment_api_kakaloan_2_unionpay extends Abstract_payment_api_kakaloan_2 {

	public function getPlatformCode() {
		return KAKALOAN_2_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kakaloan_2_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info, $amount) {
		$params['amount'] = $this->convertAmountToCurrency($amount); //分
		$params['nonce_str'] = $this->uuid();
	}

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

   	public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s%s', str_split(bin2hex($data), 4));
	}

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
