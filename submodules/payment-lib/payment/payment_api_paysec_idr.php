<?php

require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

Class payment_api_paysec_idr extends Abstract_payment_api_paysec
{
    public $payType = 'IDR';

    public function getPrefix()
    {
        return 'paysec_idr';
	}

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode()
    {
		return PAYSEC_IDR_PAYMENT_API;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) 
    {
		return $this->processPaymentUrlFormPost($params);
	}
}