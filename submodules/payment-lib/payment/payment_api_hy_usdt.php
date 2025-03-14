<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hy_usdt.php';
/**
 * HY_USDT
 *
 * * HY_USDT_PAYMENT_API, ID: 5882
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: https://api.coinopayment.com/api/v1/pay
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hy_usdt extends Abstract_payment_api_hy_usdt {

    public function getPlatformCode() {
        return HY_USDT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hy_usdt';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['crypto_amount']  = $extraInfo['crypto_amount'];
            }
        }
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang("CN Yuan")),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
