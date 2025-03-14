<?php
require_once dirname(__FILE__) . '/payment_api_onepay.php';
/**
 * FPGPAY
 *
 * * FPGPAY_QUICKPAY_PAYMENT_API, ID: 5421
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.fpglink.com/payment/v3/checkOut.html
 * * Extra Info:
 * > {
 * >    "onepay_priv_key": "## Private Key ##",
 * >    "onepay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fpgpay_quickpay extends Payment_api_onepay {

    public function getPlatformCode() {
        return FPGPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fpgpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType']         = 'NC';
        $params['cardType']        = 'D';

        if($this->CI->utils->is_mobile()) {
            $params['deviceType'] = 'H5';
        }else{
            $params['deviceType'] = 'WEB';
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
        );
    }
}
