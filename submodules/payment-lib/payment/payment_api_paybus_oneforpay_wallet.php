<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_ONEFORPAY_WALLET_PAYMENT_API, ID: 6431
 *
 * Field Values:
 * * URL: https://stg-doc.paybus.io/open.html
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_oneforpay_wallet extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_ONEFORPAY_WALLET_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_oneforpay_wallet';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($params['client_id'])) {
            $sale_order = $this->sale_order->getSaleOrderBySecureId($params['client_id']);
            $playerDetails = $this->CI->player_model->getPlayerDetails($sale_order->player_id);
        }
        $firstname = (!empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'none';
        $lastname  = (!empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'none';
        $username  = (!empty($playerDetails[0]['username']))  ? $playerDetails[0]['username']  : 'none';

        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_ONEFORPAY_WALLET => [
                "membercode" => $username,
                "fullname" => $lastname.' '.$firstname
            ]
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