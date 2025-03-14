<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yuepay.php';

/**
 *
 * * YUEPAY_UNIONPAY_PAYMENT_API,        ID: 808 銀聯
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxin
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yuepay_unionpay extends Abstract_payment_api_yuepay {

    public function getPlatformCode() {
        return YUEPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yuepay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['tunnelcode'] = self::TUNNEL_CODE_UNIONPAY;
        $params['paymenttypeid'] = self::TUNNEL_PAYMENTTYPEID;
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
