<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sanlian.php';
/**
 * SANLIAN_USDT
 *
 * * SANLIAN_USDT_PAYMENT_API, ID: 5935
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.request_key
 * * extra_info.callback_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.request_key      ## request key ##
 * * extra_info.callback_key     ## callback key ##
 *
 * @see         abstract_payment_api_sanlian.php
 * @category    Payment
 * @copyright   2022 tot
 */
class Payment_api_sanlian_usdt extends Abstract_payment_api_sanlian {

    public function getPlatformCode() {
        return SANLIAN_USDT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sanlian_usdt';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_id']   = self::PAY_REQ_PAY_ID_USDT;
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['crypto_amount']  = $this->convertAmountToCurrency($extraInfo['crypto_amount']);
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang("CN Yuan")),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09', 'readonly' => 1)
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}