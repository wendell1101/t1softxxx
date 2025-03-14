<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newspay.php';
/**
 * newspay
 * *
 * * NEWSPAY_PAYMENT_API, ID: 5984
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Token
 *
 * Field Values:
 * * URL: https://onepay.news/api/v1/order/receive
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Token: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_newspay extends Abstract_payment_api_newspay {

    public function getPlatformCode() {
        return NEWSPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'newspay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payCode'] = $this->getSystemInfo("payCode");
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
