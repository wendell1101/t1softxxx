<?php
require_once dirname(__FILE__) . '/abstract_payment_api_superstarpay.php';
/**
 * SUPERSTARPAY 超级星
 *
 * * SUPERSTARPAY_UNIONPAY_PAYMENT_API, ID: 930
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://www.superstarpay.com/gateway/orderPay
 * * Extra Info:
 * > {
 * >    "superstarpay_priv_key": "## Private Key ##",
 * >    "superstarpay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_superstarpay_unionpay extends Abstract_payment_api_superstarpay {

    public function getPlatformCode() {
        return SUPERSTARPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'superstarpay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType']  = self::PAYTYPE_UNIONPAY;
        $params['appSence'] = self::APPSENCE_PC;
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
