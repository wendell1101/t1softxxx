<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianfu.php';

/**
 * TIANFU 天天付
 * *
 * * TIANFU_ALIPAY_H5_PAYMENT_API, ID: 966
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.zhizeng-pay.net/mas/mobile/create.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tianfu_alipay_h5 extends Abstract_payment_api_tianfu {

    public function getPlatformCode() {
        return TIANFU_ALIPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tianfu_alipay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::CHANNEL_ALIPAY;
        $params['payType'] = self::PAYTYPE_ALIPAY_H5;
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
