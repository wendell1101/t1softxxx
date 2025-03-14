<?php

require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

Class Payment_api_paysec_idr_va extends Abstract_payment_api_paysec {
    public $payType = 'BANKTRANS';

    public function getPrefix() {
        return 'paysec';
    }

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode() {
        return PAYSEC_IDR_VA_PAYMENT_API;
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['version']  = '3.0';
        $params['channelCode'] = self::FIELD_CHANNEL_CODE_BANKTRANSFER;
        $params['bankCode'] = $this->getBankType($direct_pay_extra_info);
        if($this->getSystemInfo('use_usd_currency')) {
            $params['orderAmount'] = $this->convertAmountToCurrency($this->gameAmountToDBByCurrency($params['orderAmount'], $this->utils->getTodayForMysql(),'USD','CNY') );
        }
    }

    public function getBankType($direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        } else {
            return parent::getBankType($direct_pay_extra_info);
        }
    }

    # Config in extra_info will overwrite this
    public function getBankListInfoFallback() {
        return array(
            array('label' => 'May Bank', 'value' => 'VA'),
            array('label' => 'Permata Bank', 'value' => 'VA_PER'),
            array('label' => 'BRI Bank', 'value' => 'VA_BNI')
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}