<?php
require_once dirname(__FILE__) . '/abstract_payment_api_apcopay.php';

/**
 * apcopay
 * *
 * * APCOPAY_PAYMENT_API, ID: 6063
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.apsp.biz/MerchantTools/MerchantTools.svc/BuildXMLToken
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_apcopay extends Abstract_payment_api_apcopay {

    public function getPlatformCode() {
        return APCOPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'apcopay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
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
