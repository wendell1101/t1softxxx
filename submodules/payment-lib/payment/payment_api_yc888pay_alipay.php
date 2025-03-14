<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yc888pay.php';

/**
 *
 * YC888PAY
 *
 *
 * * 'YC888PAY_ALIPAY_PAYMENT_API', ID 6029
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://yc888pay.cc/Apipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yc888pay_alipay extends Abstract_payment_api_yc888pay {

	public function getPlatformCode() {
		return YC888PAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yc888pay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paytype'] = self::PAYWAY_ALIPAY;
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
