<?php
require_once dirname(__FILE__) . '/abstract_payment_api_corepay.php';

/**
 * corepay_bank
 *
 * * COREPAY_TRUEMONEY_PAYMENT_API, ID: 6253
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
class Payment_api_corepay_truemoney extends Abstract_payment_api_corepay {

    public function getPlatformCode() {
        return COREPAY_TRUEMONEY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'corepay_truemoney';
    }

    protected function configParams(&$unSignParams, $direct_pay_extra_info) {
        $unSignParams['bank_code'] = self::CHANNEL_TRUEMONEY;
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