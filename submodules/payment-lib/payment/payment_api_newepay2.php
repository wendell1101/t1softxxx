<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newepay.php';

/**
 * newepay2
 *
 * * NEWEPAY2_PAYMENT_API, ID: 6238
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://asdqw3ds8e3wj80opd-order.xnslxxl.com/payApi/PayApi/CreateOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_newepay2 extends Abstract_payment_api_newepay {

    public function getPlatformCode() {
        return NEWEPAY2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'newepay2';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_code'] = self::CHANNEL_TYPE_2;
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