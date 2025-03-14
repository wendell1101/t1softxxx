<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fktpay.php';
/**
 * FKTPAY_WEIXIN_PAYMENT_API, ID:581
 *
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_fktpay_weixin extends Abstract_payment_api_fktpay {

    public function getPlatformCode() {
        return FKTPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fktpay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $params['pay_type'] =self::PAYTYPE_WEIXIN;

    }

    

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09')
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
