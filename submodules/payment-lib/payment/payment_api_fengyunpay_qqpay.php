<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fengyunpay.php';

/**
 * FENGYUNPAY  风云
 *
 * * FENGYUNPAY_QQPAY_PAYMENT_API, ID: 951
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
class Payment_api_fengyunpay_qqpay extends Abstract_payment_api_fengyunpay {

	public function getPlatformCode() {
		return FENGYUNPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'fengyunpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_QQPAY;
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
