<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpay.php';

/**
 * CPAY
 *
 * * CPAY_JDPAY_PAYMENT_API,      ID: 691
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info { "cpay_priv_key" }
 *
 * Field Values:
 * * URL: https://api.dobopay.com/v1/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra Info: { "cpay_priv_key" : " ## Private Key ## "}
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cpay_jdpay extends Abstract_payment_api_cpay {

    public function getPlatformCode() {
        return CPAY_JDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cpay_jdpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        unset($params['pageurl']);
        unset($params['backurl']);
        $params['scantype'] = self::SCANTYPE_JDPAY;
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
