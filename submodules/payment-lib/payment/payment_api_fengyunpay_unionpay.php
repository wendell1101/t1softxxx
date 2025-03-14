<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fengyunpay.php';

/**
 * FENGYUNPAY  风云
 *
 * * FENGYUNPAY_UNIONPAY_PAYMENT_API, ID: 953
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: https://www.fengyunpay.net/gateway/pay
 * * Account: ## MerId ##
 * * Key: ## APIKEY ##
 * * Secret: ## TerId ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fengyunpay_unionpay extends Abstract_payment_api_fengyunpay {

	public function getPlatformCode() {
		return FENGYUNPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'fengyunpay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_UNIONPAY;
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
