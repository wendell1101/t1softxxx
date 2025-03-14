<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kjfpay.php';

/**
 * KJFPAY 快捷付
 * *
 * * KJFPAY_WEIXIN_H5_PAYMENT_API, ID: 939
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://kjfpay.seepay.net/serviceDirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kjfpay_weixin_h5 extends Abstract_payment_api_kjfpay {

    public function getPlatformCode() {
        return KJFPAY_WEIXIN_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kjfpay_weixin_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payMethod'] = self::PAYMETHOD_WEIXIN_H5;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
