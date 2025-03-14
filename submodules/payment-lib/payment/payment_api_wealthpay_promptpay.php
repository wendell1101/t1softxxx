<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wealthpay.php';

/**
 * WEALTHPAY
 * http://merchant.topasianpg.co
 *
 * * WEALTHPAY_PROMPTPAY_PAYMENT_API, ID: 5741
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.wealthpay.asia/merchant/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wealthpay_promptpay extends Abstract_payment_api_wealthpay {

    public function getPlatformCode() {
        return WEALTHPAY_PROMPTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wealthpay_promptpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['DepositChannel'] = self::DEPOSIT_CHANNEL_PROMPTPAY;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
