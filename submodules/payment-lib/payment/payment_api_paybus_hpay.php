<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_HPAY_PAYMENT_API, ID: 6378
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_hpay extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_HPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_hpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        
        $returnUrl['returnUrl']=$this->getSystemInfo('returnUrl');
        $inputobject=[
            "return_url"=>$this->getSystemInfo('returnUrl'),
            'payment_type'=>$this->getSystemInfo('payment_type')  // SCANCODE:1 // Direct Pay:3
        ];
        $params['channel_input'] = json_decode(json_encode([self::CHANNEL_HPAY_GCASH => $inputobject]));


    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
    
    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}