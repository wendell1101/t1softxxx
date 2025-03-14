<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bthpay.php';

/**
 * BTHPAY
 * http://office.bth.ph/login/
 *
 * * BTHPAY_WEIXIN_PAYMENT_API, ID: 850
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_info: bthpay_sn_key
 *
 * Field Values:
 * * URL: http://apipay.bth.ph/pay/gateway
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra_info: { "bthpay_sn_key" : "## MERCHANT_SIGN_KEY_FOR_SN ##" }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_bthpay_weixin extends Abstract_payment_api_bthpay {

    public function getPlatformCode() {
        return BTHPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bthpay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = self::PAY_TYPE_WEIXIN;
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