<?php
require_once dirname(__FILE__) . '/abstract_payment_api_diffusepay.php';

/**
 *
 * * DIFFUSEPAY_ALIPAY_PAYMENT_API', ID: 5110
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
class Payment_api_diffusepay_alipay extends Abstract_payment_api_diffusepay {

    public function getPlatformCode() {
        return DIFFUSEPAY_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'diffusepay_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_bankcode'] = self::PAY_BANKCODE_ALIPAY;
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
