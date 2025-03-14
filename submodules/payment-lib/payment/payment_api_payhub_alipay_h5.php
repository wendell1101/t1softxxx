<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payhub.php';
/**
 * PAYHUB 365PAYHUB
 * *
 * * PAYHUB_ALIPAY_H5_PAYMENT_API, ID: 5015
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Token
 *
 * Field Values:
 * * URL: https://www.365payhub8.com/api/pay_qr
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Token: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_payhub_alipay_h5 extends Abstract_payment_api_payhub {

    public function getPlatformCode() {
        return PAYHUB_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'payhub_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_ALIPAY;
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
