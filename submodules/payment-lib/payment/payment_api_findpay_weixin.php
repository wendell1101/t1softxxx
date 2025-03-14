<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bgpay.php';

/**
 * FINDPAY 寻找支付
 * *
 * * FINDPAY_WEIXIN_PAYMENT_API, ID: 5361
 *
 * Required Fields:
 * * URL: http://fp.find-pay/pay
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_findpay_weixin extends Abstract_payment_api_bgpay {

    public function getPlatformCode() {
        return FINDPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'findpay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
            $params['fxpay'] = $this->getSystemInfo('fxpay',self::FXPAY_WEIXIN);
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
