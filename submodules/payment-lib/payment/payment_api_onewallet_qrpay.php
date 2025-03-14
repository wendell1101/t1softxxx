<?php
require_once dirname(__FILE__) . '/abstract_payment_api_onewallet.php';

/**
 * ONEWALLET
 *
 * * ONEWALLET_QRPAY_PAYMENT_API, ID: 5683
 * * ONEWALLET_QRPAY_2_PAYMENT_API, ID: 5684
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://api-tg.100scrop.tech/11-dca/SH/sendPay
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_onewallet_qrpay extends Abstract_payment_api_onewallet {

    public function getPlatformCode() {
        return ONEWALLET_QRPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'onewallet_qrpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['order_type'] = self::ORDER_TYPE_QRPAY;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        /*
            1.
            Valid [float_amount_limit] pattern

            pattern: "float_amount_limit": "(A|B|C|D|E|F|...)"

            A: limit amount 1
            B: limit amount 2
            C: limit amount 3

            example: "float_amount_limit": "(1|21|51)"

            2.
            show [float_amount_limit_msg]  when amount is incorrect
        */
        $type = $this->getSystemInfo('float_amount_limit')? 'float_amount_limit' : 'float_amount' ;
        $float_amount_limit_msg = $this->getSystemInfo('float_amount_limit_msg')?$this->getSystemInfo('float_amount_limit_msg'):'請輸入上方金額';
        if($type == 'float_amount_limit'){
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'float_amount_limit_msg' => $float_amount_limit_msg),
            );
        }else{
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09'),
            );
        }
    }
}
