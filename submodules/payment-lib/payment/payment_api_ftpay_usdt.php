<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ftpay.php';

/**
 * ftpay
 *
 * * FTPAY_PAYMENT_API, ID: 6263
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://jst-168u.cc/Apipay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_ftpay_usdt extends Abstract_payment_api_ftpay {

    public function getPlatformCode() {
        return FTPAY_USDT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ftpay_usdt';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['amount']  = $extraInfo['crypto_amount'];
            }
        }
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang('BRL-Yuan')),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}