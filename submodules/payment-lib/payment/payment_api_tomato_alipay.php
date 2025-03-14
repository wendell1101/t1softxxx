<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tomato.php';

/**
 * tomato
 *
 * * TOMATO_ALIPAY_PAYMENT_API, ID: 5910
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://dsdf.tomato-pay.com/api/startOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tomato_alipay extends Abstract_payment_api_tomato {

    public function getPlatformCode() {
        return TOMATO_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tomato_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAY_CODE_ALIPAY;
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