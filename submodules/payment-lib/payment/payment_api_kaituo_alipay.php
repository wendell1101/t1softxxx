<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kaituo.php';
/**
 * KAITUO
 *
 * * KAITUO_ALIPAY_PAYMENT_API, ID: 5030
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pos.kaituocn.com/payapi/v2/Payinit/genQrCode
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kaituo_alipay extends Abstract_payment_api_kaituo {

    public function getPlatformCode() {
        return KAITUO_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kaituo_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['trade_type'] = self::TRADE_TYPE_H5;
            $params['payment']    = self::PAYMENT_ALIPAY_H5;
            $params['wap_url']    = $this->CI->utils->site_url_with_http();
            $params['wap_name']   = $this->getSystemInfo('wap_name','OG');
        }else{
            $params['trade_type'] = self::TRADE_TYPE_PC;
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        if($this->CI->utils->is_mobile()) {
            return $this->processPaymentUrlFormPost($params);
        }else{
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
