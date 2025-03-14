<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zpays.php';
/**
 *
 * * ZPAYS_USDT_PAYMENT_API, ID: 6240
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.zm-pay.com/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_zpays_usdt extends Abstract_payment_api_zpays {
    public function getPlatformCode() {
        return ZPAYS_USDT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'zpays_usdt';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] =  $this->getSystemInfo("productId", self::CHANNEL_TYPE_USDT);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['amount']  = (int)$extraInfo['crypto_amount'] * 100;
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang('BRL-Yuan')),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormForRedirect($params);
    }

}