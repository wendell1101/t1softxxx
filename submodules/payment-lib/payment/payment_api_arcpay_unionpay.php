<?php
require_once dirname(__FILE__) . '/abstract_payment_api_arcpay.php';

/**
 * ARCPAY 大强
 * *
 * * ARCPAY_UNIONPAY_PAYMENT_API, ID: 909
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.arcpay.info/gateway/payApi/PayApiController/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_arcpay_unionpay extends Abstract_payment_api_arcpay {

    public function getPlatformCode() {
        return ARCPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'arcpay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_UNIONPAY;
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
