<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paysec_v2.php';
/**
 * PAYSEC_V2
 *
 * * PAYSEC_WEIXIN_V2_PAYMENT_API, ID: 630
 * *
 * Required Fields:
 * * Account
 * * Secret
 * * URL
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Secret: ## Merchant Key ##
 * * URL: https://payment.allpay.site/api/transfer/v1/payIn/sendTokenForm
 * * TOKEN URL: https://payment.allpay.site/api/transfer/v1/payIn/requestToken
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paysec_v2_weixin extends Abstract_payment_api_paysec_v2 {

    public function getPlatformCode() {
        return PAYSEC_WEIXIN_V2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paysec_v2_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channelCode'] = self::CHANNEL_WEIXIN;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
