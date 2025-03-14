<?php

require_once dirname(__FILE__) . '/abstract_payment_api_qingpay.php';

Class payment_api_qingpay_alipay_h5 extends Abstract_payment_api_qingpay
{
    public function getPrefix()
    {
        return 'qingpay_alipay_h5';
	}

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode()
    {
        return QINGPAY_ALIPAY_H5_PAYMENT_API;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        return $params['bank'] = 'ALIPAY';
    }

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