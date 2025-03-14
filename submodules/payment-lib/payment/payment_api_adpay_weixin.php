<?php
require_once dirname(__FILE__) . '/abstract_payment_api_adpay.php';

/**
 *
 * * ADPAY_WEIXIN_PAYMENT_API, ID: 5603
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://149.129.77.20:9595/deposit/AD001001/forward
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_adpay_weixin extends Abstract_payment_api_adpay {

	public function getPlatformCode() {
		return ADPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'adpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['gateway'] = self::GATEWAY_WEIXIN;
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
