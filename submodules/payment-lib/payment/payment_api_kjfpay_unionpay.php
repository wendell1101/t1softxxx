<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kjfpay.php';

/**
 * KJFPAY 快捷付
 * *
 * * KJFPAY_UNIONPAY_PAYMENT_API, ID: 940
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://kjfpay.seepay.net/serviceDirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kjfpay_unionpay extends Abstract_payment_api_kjfpay {

    public function getPlatformCode() {
        return KJFPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kjfpay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payMethod'] = self::PAYMETHOD_UNIONPAY;
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
