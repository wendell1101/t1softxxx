<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ffpay.php';

/**
 * FFPAY
 *
 * * FFPAY_ALIPAY_PAYMENT_API, ID: 5103
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.ffpay.net/api/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ffpay_alipay extends Abstract_payment_api_ffpay {

    public function getPlatformCode() {
        return FFPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ffpay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['BankCode'] = self::BANKCODE_ALIPAY_H5;
        }
        else {
            $params['BankCode'] = self::BANKCODE_ALIPAY;
        }
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        if($this->CI->utils->is_mobile()) {
            return $this->processPaymentUrlFormRedirect($params);
        }
        else{
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
