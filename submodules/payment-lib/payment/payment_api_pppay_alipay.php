<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pppay.php';

/**
 *
 * PPpay
 *
 *
 * * PPPAY_ALIPAY_PAYMENT_API, ID: 546
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: pppay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pppay_alipay extends Abstract_payment_api_pppay {

    public function getPlatformCode() {
        return PPPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pppay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

//        if($this->CI->utils->is_mobile()){
//            $params['payment_1'] = self::PAYTYPE_ALIPAY_H5.$params['requestAmount'];
//        }else{
            $params['payment_1'] = self::PAYTYPE_ALIPAY.$params['requestAmount'];
//        }
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
