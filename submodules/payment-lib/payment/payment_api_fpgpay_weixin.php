<?php
require_once dirname(__FILE__) . '/abstract_payment_api_onepay.php';
/**
 * FPGPAY
 *
 * * FPGPAY_WEIXIN_PAYMENT_API, ID: 5422
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.fpglink.com/payment/otoSoft/v3/onlinePay.html
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
class Payment_api_fpgpay_weixin extends Abstract_payment_api_onepay {

    public function getPlatformCode() {
        return FPGPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fpgpay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payment_channel'] = self::CHANNEL_WEIXIN;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
