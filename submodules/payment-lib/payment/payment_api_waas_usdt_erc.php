<?php
require_once dirname(__FILE__) . '/abstract_payment_api_waas_usdt.php';
/**
 * WAAS_USDT_ERC
 *
 * * WAAS_USDT_ERC_PAYMENT_API, ID: 6038
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
class Payment_api_waas_usdt_erc extends Abstract_payment_api_waas_usdt {

    public function getPlatformCode() {
        return WAAS_USDT_ERC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'waas_usdt_erc';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['crypto_amount']  = $extraInfo['crypto_amount'];
                $params['data']['symbol'] = $this->getSystemInfo('crypto_currency');
                $params['is_pcf_api'] = isset($extraInfo['is_pcf_api']) ? $extraInfo['is_pcf_api'] : false;
            }
        }
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang('BRL-Yuan')),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }
}
