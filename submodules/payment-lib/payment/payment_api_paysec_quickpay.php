<?php

require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

Class Payment_api_paysec_quickpay extends Abstract_payment_api_paysec {
    public $payType = 'QUICKPAY';

    public function getPrefix() {
        return 'paysec_quickpay';
    }

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode() {
        return PAYSEC_QUICKPAY_PAYMENT_API;
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['version']  = '3.0';
        $params['channelCode'] = self::FIELD_CHANNEL_CODE_BANKTRANSFER;
        $params['bankCode'] =  self::FIELD_CHANNEL_CODE_QUICK;
        if($this->getSystemInfo('use_usd_currency')) {
            $params['orderAmount'] = $this->convertAmountToCurrency($this->gameAmountToDBByCurrency($params['orderAmount'], $this->utils->getTodayForMysql(),'USD','CNY') );
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09')
        );
    }
}