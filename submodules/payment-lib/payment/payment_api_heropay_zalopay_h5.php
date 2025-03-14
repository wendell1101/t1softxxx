<?php
require_once dirname(__FILE__) . '/abstract_payment_api_heropay.php';

/**
 *
 * HEROPAY
 *
 * * HEROPAY_ZALOPAY_H5_PAYMENT_API, ID: 5812
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.251.34.145:3020/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_heropay_zalopay_h5 extends Abstract_payment_api_heropay {

	public function getPlatformCode() {
		return HEROPAY_ZALOPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'heropay_zalopay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = $this->getSystemInfo('productId');
	}

	protected function processPaymentUrlForm($params) {

        return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
