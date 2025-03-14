<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tspay.php';

/**
 * tspay
 *
 * * TSPAY_PAYMENT_API, ID: 6203
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://119.29.115.76/preCreate
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tspay extends Abstract_payment_api_tspay {

    public function getPlatformCode() {
        return TSPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tspay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::CHANNEL_TYPE_PIX;
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