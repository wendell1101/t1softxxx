<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pppay.php';

/**
 *
 * PPpay 微信H5
 *
 *
 * * PPPAY_WEIXIN_H5_PAYMENT_API, ID: 519
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: pppay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pppay_weixin_h5 extends Abstract_payment_api_pppay {

    public function getPlatformCode() {
        return PPPAY_WEIXIN_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pppay_weixin_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $params['payment_1'] = self::PAYTYPE_WEIXIN_H5.$params['requestAmount'];
        $params['extend'] = 'app_name=xxx&package_name=com.tencent.tmgp.sgame'; //微信H5必傳,其餘免

    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
