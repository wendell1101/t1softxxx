<?php
require_once dirname(__FILE__) . '/abstract_payment_api_giglobal.php';

/**
 *
 * * GIGLOBAL_QQPAY_PAYMENT_API, ID: 5598
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
class Payment_api_giglobal_qqpay extends Abstract_payment_api_giglobal {

	public function getPlatformCode() {
		return GIGLOBAL_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'giglobal_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['ordertype'] = self::ORDERTYPE_QQPAY;
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
