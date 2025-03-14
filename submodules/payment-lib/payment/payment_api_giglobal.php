<?php
require_once dirname(__FILE__) . '/abstract_payment_api_giglobal.php';

/**
 *
 * * GIGLOBAL_ALIPAY_PAYMENT_API, ID: 5595
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
class Payment_api_giglobal extends Abstract_payment_api_giglobal {

	public function getPlatformCode() {
		return GIGLOBAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'giglobal';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['ordertype'] = self::ORDERTYPE_BANK;
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
