<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vnpay.php';
/**
 * VNPAY
 *
 * * VNPAY_BANKCARD_PAYMENT_API, ID: 5815
 *
 * Required Fields:
 * * URL
 * * Key
 * * uid (merchant ID)
 *
 * Field Values:
 * * URL        : https://manager.vnpay.center/pay
 * * Key        : ## Live key ##
 * * uid        : ## merchant ID ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vnpay_bankcard extends Abstract_payment_api_vnpay {

    public function getPlatformCode() {
        return VNPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'vnpay_bankcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::CHANNEL_DEPOSIT_BANKCARD;
    }

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
           show [deposit_hint]  when amount is incorrect
        */
        $type = $this->getSystemInfo('float_amount_limit') ? 'float_amount_limit' : 'float_amount' ;
        $deposit_hint = $this->getSystemInfo('deposit_hint') ? $this->getSystemInfo('deposit_hint') : '請輸入上方金額';
        if ($type == 'float_amount_limit') {
            return [
                [ 'name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'deposit_hint' => $deposit_hint
                ]
            ];
          }
          else {
              return [
                  [ 'name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09' ],
              ];
          }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}