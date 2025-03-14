<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_JSTPAY_MOMO_PAY_PAYMENT_API, ID: 6603
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_jstpay_momo_pay extends Abstract_payment_api_paybus {

    const CHANNEL_JSTPAY_MOMO_PAY = 'jstpay.momo_pay';

    public function getPlatformCode() {
        return PAYBUS_JSTPAY_MOMO_PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_jstpay_momo_pay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($params['client_id'])) {
            $sale_order = $this->sale_order->getSaleOrderBySecureId($params['client_id']);
            $playerDetails = $this->CI->player_model->getPlayerDetails($sale_order->player_id);
        }
        $username  = (!empty($playerDetails[0]['username']))  ? $playerDetails[0]['username']  : 'none';

        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_JSTPAY_MOMO_PAY => [
                "UID" => $username,
                "IPAddress" => $this->getClientIP()
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