<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nopay.php';
/**
 * NOPAY
 *
 * * NOPAY_PAYMENT_API, ID: 6330
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: 
 * * Extra Info:
 * > {
 * >    "protocol": TRC20/ERC20,
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_nopay_btc extends Abstract_payment_api_nopay {

    public function getPlatformCode() {
        return NOPAY_BTC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nopay_btc';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['coin']          = "BTC";
        $params['protocol']      = $this->getSystemInfo('protocol')? $this->getSystemInfo('protocol') : "BTC";

        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['crypto_amount']  = $extraInfo['crypto_amount'];
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang(self::COIN_BTC)), 'crypto_currency_lang' => lang(self::COIN_BTC), 'default_currency_lang' => lang('BRL-Yuan')),
        );
        
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
