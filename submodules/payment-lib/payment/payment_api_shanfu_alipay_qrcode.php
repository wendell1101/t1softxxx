<?php
require_once dirname(__FILE__) . '/abstract_payment_api_shanfu.php';

/**
 *
 * * SHANFU_ALIPAY_QRCODE_PAYMENT_API, ID: 5734
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://sfpay8.com/api/gateway/index.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_shanfu_alipay_qrcode extends Abstract_payment_api_shanfu {

	public function getPlatformCode() {
		return SHANFU_ALIPAY_QRCODE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'shanfu_alipay_qrcode';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_type'] = self::ORDERTYPE_ALIPAY_QRCODE;
		$params['pay_code'] = self::ORDERCODE_ALIPAY_QRCODE;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}
