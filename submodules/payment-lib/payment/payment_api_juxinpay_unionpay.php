<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juxinpay.php';

/**
 *
 * * JUXINPAY_UNIONPAY_PAYMENT_API, ID: 527
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxinpay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juxinpay_unionpay extends Abstract_payment_api_juxinpay {

    public function getPlatformCode() {
        return JUXINPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'juxinpay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $params['payType'] = self::PAYTYPE_UNIONPAY;
        $params['bankCode'] = self::BANKCODE_UNIONPAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
