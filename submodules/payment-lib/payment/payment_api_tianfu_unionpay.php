<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianfu.php';

/**
 * TIANFU 天天付
 * *
 * * TIANFU_UNIONPAY_PAYMENT_API, ID: 967
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
class Payment_api_tianfu_unionpay extends Abstract_payment_api_tianfu {

    public function getPlatformCode() {
        return TIANFU_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tianfu_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::CHANNEL_UNIONPAY;
        if($this->CI->utils->is_mobile()) {
            $params['payType'] = self::PAYTYPE_UNIONPAY_H5;
        }
        else {
            $params['payType'] = self::PAYTYPE_UNIONPAY;
        }
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        if($this->CI->utils->is_mobile() || $this->getSystemInfo('useUrlFormpost') == true) {
            return $this->processPaymentUrlFormPost($params);
        }
        else {
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
