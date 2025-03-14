<?php
require_once dirname(__FILE__) . '/abstract_payment_api_findpay.php';

/**
 * FINDPAY 寻找支付
 * *
 * * FINDPAY_ALIPAY_PAYMENT_API, ID: 5568
 *
 * Required Fields:
 * * URL:
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_findpay_quickpay extends Abstract_payment_api_findpay {

    public function getPlatformCode() {
        return FINDPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'findpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['fxpay'] = $this->getSystemInfo('fxpay',self::FXPAY_QUICKPAY);
        $params['fxuserid'] = $this->getSystemInfo('account');  # 快捷模式绑定商户 id
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
