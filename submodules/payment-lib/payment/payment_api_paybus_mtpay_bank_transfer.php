<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_MTPAY_BANK_TRANSFER_PAYMENT_API, ID: 6442
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_mtpay_bank_transfer extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_MTPAY_BANK_TRANSFER_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_mtpay_bank_transfer';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($params['client_id'])) {
            $sale_order = $this->sale_order->getSaleOrderBySecureId($params['client_id']);
            $playerDetails = $this->CI->player_model->getPlayerDetails($sale_order->player_id);
        }
        $username  = (!empty($playerDetails[0]['username']))  ? $playerDetails[0]['username']  : 'none';

        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_MTPAY_BANK_TRANSFER => [
                "userinfo" => $username,
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