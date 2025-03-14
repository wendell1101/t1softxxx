<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stonpay.php';

/**
 *
 * * STONPAY_ALIPAY_PAYMENT_API', ID: 5084
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_stonpay_alipay extends Abstract_payment_api_stonpay {

    public function getPlatformCode() {
        return STONPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'stonpay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['pType'] = self::PTYPE_ALIPAY_H5;
        }
        else {
            $params['pType'] = self::PTYPE_ALIPAY;
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
            return $this->processPaymentUrlFormPost($params);
        }
        else {
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
