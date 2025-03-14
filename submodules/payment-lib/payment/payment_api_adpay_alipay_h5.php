<?php
require_once dirname(__FILE__) . '/abstract_payment_api_adpay.php';

/**
 *
 * * ADPAY_ALIPAY_H5_PAYMENT_API, ID: 5580
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://149.129.77.20:9595/deposit/AD001001/mobile/forward
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_adpay_alipay_h5 extends Abstract_payment_api_adpay {

	public function getPlatformCode() {
		return ADPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'adpay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['gateway'] = self::GATEWAY_MOBILE_ALIPAY;
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
