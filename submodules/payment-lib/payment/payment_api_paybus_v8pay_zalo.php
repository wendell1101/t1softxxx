<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_V8PAY_ZALO_PAYMENT_API, ID: 6589
 *
 * Field Values:
 * * URL: https://stg-open.paybus.io
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_v8pay_zalo extends Abstract_payment_api_paybus {

    const CHANNEL_V8PAY_ZALO = 'v8pay.zalo';

    public function getPlatformCode() {
        return PAYBUS_V8PAY_ZALO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_v8pay_zalo';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $playerDetails = $params['playerDetails'];
        $firstname = (!empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
        $lastname  = (!empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';

        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_V8PAY_ZALO => array(
                "user" => $lastname.' '.$firstname
            )
        ]));
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
    
}