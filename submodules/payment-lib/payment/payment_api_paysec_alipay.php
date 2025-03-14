<?php

require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

Class payment_api_paysec_alipay extends Abstract_payment_api_paysec
{
    public $payType = 'ALIPAY';

    public function getPrefix()
    {
        return 'paysec';
	}

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode()
    {
		return PAYSEC_ALIPAY_PAYMENT_API;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) 
    {
		return $this->processPaymentUrlFormQRCode($params);
	}

    public function getPlayerInputInfo() 
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}