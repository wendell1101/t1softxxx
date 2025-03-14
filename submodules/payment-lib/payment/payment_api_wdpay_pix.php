<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wdpay.php';

/**
 * fortunepay
 *
 * * WDPAY_PIX_PAYMENT_API, ID: 6572
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Access key ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wdpay_pix extends Abstract_payment_api_wdpay {

    public function getPlatformCode() {
        return WDPAY_PIX_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wdpay_pix';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo(){
        return [
            [
                'name' => 'deposit_amount', 
                'type' => 'float_amount', 
                'label_lang' => 'cashier.09'
            ]
        ];
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
