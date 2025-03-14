<?php
require_once dirname(__FILE__) . '/abstract_payment_api_giglobal.php';

/**
 *
 * * GIGLOBAL_JDPAY_PAYMENT_API, ID: 5602
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gp.gi-global.com:817/order/initOrder.aspx
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_giglobal_jdpay extends Abstract_payment_api_giglobal {

	public function getPlatformCode() {
		return GIGLOBAL_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'giglobal_jdpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['ordertype'] = self::ORDERTYPE_JDPAY;
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
