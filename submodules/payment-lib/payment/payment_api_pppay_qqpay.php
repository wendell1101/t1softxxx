<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pppay.php';

/**
 *
 * PPpay
 *
 *
 * * PPPAY_QQPAY_PAYMENT_API, ID: 547
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: pppay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pppay_qqpay extends Abstract_payment_api_pppay {

    public function getPlatformCode() {
        return PPPAY_QQPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pppay_qqpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $params['payment_1'] = self::PAYTYPE_QQPAY.$params['requestAmount'];
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
