<?php
require_once dirname(__FILE__) . '/abstract_payment_api_qpgpay.php';

/**
 * qpgpay
 *
 * * QPGPAY_PAYMENT_API, ID: 5977
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://web.jf3092.com/paygate/pay.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qpgpay extends Abstract_payment_api_qpgpay {

    public function getPlatformCode() {
        return QPGPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'qpgpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channelType'] = self::DEPOSIT_CHANNEL_BANK;
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }

    public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
