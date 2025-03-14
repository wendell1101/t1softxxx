<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ffpay.php';

/**
 * FFPAY
 *
 * * FFPAY_QUICKPAY_PAYMENT_API, ID: 5140
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.ffpay.net/api/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ffpay_quickpay extends Abstract_payment_api_ffpay {

    public function getPlatformCode() {
        return FFPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ffpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['BankCode'] = self::BANKCODE_QUICKPAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
