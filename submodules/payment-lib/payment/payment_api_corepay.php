<?php
require_once dirname(__FILE__) . '/abstract_payment_api_corepay.php';

/**
 * corepay
 *
 * * COREPAY_PAYMENT_API, ID: 6250
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://vippay.corepaypro.com/trade/repay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_corepay extends Abstract_payment_api_corepay {

    public function getPlatformCode() {
        return COREPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'corepay';
    }

    protected function configParams(&$unSignParams, $direct_pay_extra_info) {
        $unSignParams['bank_code'] = self::CHANNEL_PROMPTPAY;
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}