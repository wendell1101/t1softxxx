<?php
require_once dirname(__FILE__) . '/abstract_payment_api_glpays.php';

/**
 * GLPAYS
 * *
 * * GLPAYS_QUICKPAY_PAYMENT_API, ID: 5326
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.glpays.com/wpay/api?do=CreateOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_glpays_quickpay extends Abstract_payment_api_glpays {

    public function getPlatformCode() {
        return GLPAYS_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'glpays_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
            $params['trade_type'] = self::PAYTYPE_QUICK_PAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
            return $this->processPaymentUrlFormQRCode($params);
    }
}
