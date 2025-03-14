<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hpay.php';

/**
 * HPAY_ALIPAY
 *
 * * HPAY_ALIPAY_H5_PAYMENT_API, ID: 5788
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.hpay8.com/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hpay_alipay_h5 extends Abstract_payment_api_hpay {

	public function getPlatformCode() {
		return HPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hpay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['paytype'] = self::PAY_TYPE_ID_ALIPAY_H5;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormURL($params);
	}

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
