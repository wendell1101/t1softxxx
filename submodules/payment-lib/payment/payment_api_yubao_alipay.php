<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yubao.php';

/**
 *
 * * YUBAO_ALIPAY_PAYMENT_API, ID: 5652
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.zjyiruibao.net/AliPayment.php
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yubao_alipay extends Abstract_payment_api_yubao {

	public function getPlatformCode() {
		return YUBAO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yubao_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['MerProductID'] = self::PRODUCT_ID;
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
