<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sevpay.php';

/**
 * SEVPAY
 * * http://merchant.777office.com/
 *
 * * SEVPAY_PAYMENT_API, ID: 910
 * * SEVPAY_QUICKPAY_PAYMENT_API, ID: 911
 * * SEVPAY_ALIPAY_PAYMENT_API, ID: 912
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.paynow777.com/merchanttransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sevpay extends Abstract_payment_api_sevpay {

    public function getPlatformCode() {
        return SEVPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sevpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->getSystemInfo('currency','CNY') == 'CNY'){
            $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
            if (!empty($direct_pay_extra_info)) {
                $extraInfo = json_decode($direct_pay_extra_info, true);
                if (!empty($extraInfo)) {
                    $params['Bank'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
                }
            }
        }
    }

    public function getPlayerInputInfo() {
        if($this->getSystemInfo('currency','CNY') == 'IDR'){
            return array(
                array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            );
        }
        else{
            return parent::getPlayerInputInfo();
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
