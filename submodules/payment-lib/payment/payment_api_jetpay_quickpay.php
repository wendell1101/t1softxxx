<?php
require_once dirname(__FILE__) . '/abstract_payment_api_jetpay.php';
/**
 * JetPay 捷智付
 *
 * * JETPAY_QUICKPAY_PAYMENT_API, ID: 5080
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://39.98.88.140:8082/pp_server/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jetpay_quickpay extends Abstract_payment_api_jetpay {

    public function getPlatformCode() {
        return JETPAY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'jetpay_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['tranName'] = self::TRANTYPE_QUICKPAY;
        $params['payType']  = self::SCANTYPE_QUICKPAY;
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['card_number'])){
                $params['reservedField1'] = $extraInfo['card_number'];
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
