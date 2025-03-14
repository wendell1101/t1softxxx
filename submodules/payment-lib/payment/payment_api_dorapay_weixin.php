<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dorapay.php';

/**
 *
 * * DORAPAY_WEIXIN_PAYMENT_API', ID: 721
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
class Payment_api_dorapay_weixin extends Abstract_payment_api_dorapay {

    public function getPlatformCode() {
        return DORAPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dorapay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['biz_content']['channel_code'] = self::PAYTYPE_WEIXIN;
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
