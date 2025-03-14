<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kaituo.php';
/**
 * KAITUO
 *
 * * KAITUO_ALIPAY_H5_PAYMENT_API, ID: 5031
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
class Payment_api_kaituo_alipay_h5 extends Abstract_payment_api_kaituo {

    public function getPlatformCode() {
        return KAITUO_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kaituo_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['trade_type'] = self::TRADE_TYPE_H5;
        $params['payment']    = self::PAYMENT_ALIPAY_H5;
        $params['wap_url']    = $this->CI->utils->site_url_with_http();
        $params['wap_name']   = $this->getSystemInfo('wap_name','OG');
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
