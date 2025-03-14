<?php
require_once dirname(__FILE__) . '/abstract_payment_api_anxinpay.php';
/**
 * Anxinpay 安心支付
 *
 * * ANXINPAY_QUICKPAY_PAYMENT_API, ID: 5433
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: http://pay.aixinyu.cn/createOrder
 * * Extra Info:
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_anxinpay_quickpay extends Abstract_payment_api_anxinpay {

    public function getPlatformCode() {
        return ANXINPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'anxinpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['tradeType'] = self::PAYTYPE_QUICKPAY;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
