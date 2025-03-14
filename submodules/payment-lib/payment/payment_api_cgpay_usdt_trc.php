<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cgpay.php';
/**
 * CGPAY_TRC_USDT
 *
 * * CGPAY_TRC_USDT_PAYMENT_API, ID: 5882
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
class Payment_api_cgpay_usdt_trc extends Abstract_payment_api_cgpay {

    public function getPlatformCode() {
        return CGPAY_USDT_TRC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cgpay_usdt_trc';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['Amount']        = $this->convertAmountToCurrency($extraInfo['crypto_amount']);
                $params['AnchoredRMB']   = $params['temp_amount'];
                $params['Symbol']        = self::PAYWAY_USDT_ERC;
                $params['crypto_amount'] = $extraInfo['crypto_amount'];
                $params['rate']          = $extraInfo['deposit_crypto_rate'];
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
